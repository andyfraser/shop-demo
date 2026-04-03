<?php
namespace App\Services;

class AuthService {
    public static function sessionStart(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function currentUser(): ?array {
        self::sessionStart();
        return $_SESSION['user'] ?? null;
    }

    public static function isAdmin(): bool {
        $u = self::currentUser();
        return $u && $u['role'] === 'admin';
    }

    public static function login(array $user): void {
        self::sessionStart();
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
    }

    public static function logout(): void {
        self::sessionStart();
        session_regenerate_id(true);
        session_destroy();
    }
}
