<?php
namespace App\Services;

use App\Models\User;
use App\Repositories\AuthRepositoryInterface;
use Psr\Log\LoggerInterface;
use App\Core\Events\EventDispatcherInterface;

class AuthService implements AuthServiceInterface {
    private const COOKIE_NAME = 'remember_token';
    private ?User $cachedUser = null;

    public function __construct(
        private AuthRepositoryInterface $repository,
        private SettingsServiceInterface $settings,
        private LoggerInterface $logger,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function sessionStart(): void {
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                session_start();
            } elseif (!isset($_SESSION)) {
                $_SESSION = [];
            }
        }
    }

    public function currentUser(): ?User {
        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }

        // Check for Bearer token first
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $user = $this->verifyApiToken($matches[1]);
            if ($user) {
                $this->cachedUser = $user;
                return $user;
            }
        }

        $this->sessionStart();
        
        $user = null;
        if (isset($_SESSION['user'])) {
            $userSession = $_SESSION['user'];
            $userId = ($userSession instanceof User) ? $userSession->id : ($userSession['id'] ?? 0);
            
            if ($userId) {
                // Reload from DB to ensure role and other data are fresh
                $user = $this->repository->findUserById($userId);
                
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
                $user = $this->repository->findUserByRememberToken($token);
                
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

        $this->repository->setRememberToken($userId, $token, $expires, $oldToken);

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
            $this->repository->clearRememberToken($token);
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

    public function generateApiTokenForUser(User $user): string {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        
        $createdAt = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + (30 * 86400));
        
        $this->repository->saveApiToken($user->id, $hash, $createdAt, $expiresAt);
        return $token;
    }

    public function verifyApiToken(string $token): ?User {
        $hash = hash('sha256', $token);
        return $this->repository->findUserByApiTokenHash($hash);
    }

    public function revokeApiToken(string $token): bool {
        $hash = hash('sha256', $token);
        $this->cachedUser = null;
        return $this->repository->deleteApiTokenHash($hash);
    }
}
