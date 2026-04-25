<?php
namespace App\Services;

use App\Core\Database;

class AuthService {
    private const COOKIE_NAME = 'remember_token';

    public function __construct(
        private \PDO $db,
        private SettingsService $settings
    ) {}

    public function sessionStart(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    public function currentUser(): ?array {
        $this->sessionStart();
        if (isset($_SESSION['user'])) {
            return $_SESSION['user'];
        }
        // Try to restore session from a remember-me cookie
        $token = $_COOKIE[self::COOKIE_NAME] ?? null;
        if ($token) {
            $stmt = $this->db->prepare(
                "SELECT rt.user_id, rt.expires_at, u.*
                 FROM remember_tokens rt
                 JOIN users u ON u.id = rt.user_id
                 WHERE rt.token = ? AND rt.expires_at > ?"
            );
            $stmt->execute([$token, time()]);
            $row = $stmt->fetch();
            if ($row) {
                @session_regenerate_id(true);
                $_SESSION['user'] = $row;
                // Rotate the token
                $this->setRememberCookie($row['user_id'], $token);
                return $_SESSION['user'];
            }
            // Stale cookie — clear it
            $this->clearRememberCookie($token);
        }
        return null;
    }

    public function isAdmin(): bool {
        $u = $this->currentUser();
        return $u && $u['role'] === 'admin';
    }

    public function login(array $user, bool $remember = false): void {
        $this->sessionStart();
        @session_regenerate_id(true);
        $_SESSION['user'] = $user;
        if ($remember) {
            $this->setRememberCookie($user['id']);
        }
    }

    public function logout(): void {
        $this->sessionStart();
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
