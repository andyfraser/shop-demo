<?php
namespace App\Services;

use App\Core\Database;
use App\Models\User;
use Psr\Log\LoggerInterface;

class AuthService implements AuthServiceInterface {
    private const COOKIE_NAME = 'remember_token';
    private ?User $cachedUser = null;

    public function __construct(
        private \PDO $db,
        private SettingsServiceInterface $settings,
        private LoggerInterface $logger
    ) {}

    public function sessionStart(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    public function currentUser(): ?User {
        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }

        $this->sessionStart();
        
        $user = null;
        if (isset($_SESSION['user'])) {
            $userSession = $_SESSION['user'];
            $userId = ($userSession instanceof User) ? $userSession->id : ($userSession['id'] ?? 0);
            
            if ($userId) {
                // Reload from DB to ensure role and other data are fresh
                $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, User::class, [$this->logger]);
                $stmt->execute([$userId]);
                $user = $stmt->fetch() ?: null;
                
                if ($user) {
                    $_SESSION['user'] = $user;
                } else {
                    unset($_SESSION['user']);
                }
            }
        }

        if (!$user) {
            // Try to restore session from a remember-me cookie
            $token = $_COOKIE[self::COOKIE_NAME] ?? null;
            if ($token) {
                $stmt = $this->db->prepare(
                    "SELECT u.*
                     FROM remember_tokens rt
                     JOIN users u ON u.id = rt.user_id
                     WHERE rt.token = ? AND rt.expires_at > ?"
                );
                $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, User::class, [$this->logger]);
                $stmt->execute([$token, time()]);
                $user = $stmt->fetch();
                
                if ($user) {
                    @session_regenerate_id(true);
                    $_SESSION['user'] = $user;
                    // Rotate the token
                    $this->setRememberCookie($user->id, $token);
                } else {
                    // Stale cookie — clear it
                    $this->clearRememberCookie($token);
                }
            }
        }

        $this->cachedUser = $user;
        return $user;
    }

    public function isAdmin(): bool {
        $u = $this->currentUser();
        return $u && $u->isAdmin();
    }

    public function login(array|User $user, bool $remember = false): void {
        $this->sessionStart();
        @session_regenerate_id(true);
        $this->cachedUser = null;

        if (is_array($user)) {
            $user = (new User($this->logger))->fill($user);
        }

        $_SESSION['user'] = $user;
        if ($remember) {
            $this->setRememberCookie($user->id);
        }
    }

    public function logout(): void {
        $this->sessionStart();
        $this->cachedUser = null;
        $token = $_COOKIE[self::COOKIE_NAME] ?? null;
        if ($token) {
            $this->clearRememberCookie($token);
        }
        @session_regenerate_id(true);
        unset($_SESSION['user']);
        @session_destroy();
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Create (or replace) a remember-me token for $userId.
     * If $oldToken is supplied the existing row is updated instead of inserted.
     */
    private function setRememberCookie(int $userId, ?string $oldToken = null): void {
        $days    = max(1, (int)$this->settings->get('remember_me_days'));
        $token   = bin2hex(random_bytes(32));
        $expires = time() + ($days * 86400);

        if ($oldToken) {
            $this->db->prepare("UPDATE remember_tokens SET token = ?, expires_at = ? WHERE token = ?")
                ->execute([$token, $expires, $oldToken]);
        } else {
            $this->db->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)")
                ->execute([$userId, $token, $expires]);
        }

        setcookie(
            self::COOKIE_NAME, $token, [
                'expires'  => $expires,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    /** Delete the DB row and expire the browser cookie. */
    private function clearRememberCookie(?string $token): void {
        if ($token) {
            $this->db->prepare("DELETE FROM remember_tokens WHERE token = ?")
                ->execute([$token]);
        }
        setcookie(
            self::COOKIE_NAME, '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }
}
