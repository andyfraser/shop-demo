<?php
namespace App\Services;

use App\Core\Database;

class SecurityService {
    public static function csrfToken(): string {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . h(self::csrfToken()) . '">';
    }

    public static function verifyCsrf(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $passed = $_POST['csrf_token'] ?? '';
            $stored = $_SESSION['csrf_token'] ?? '';
            if (empty($passed) || !hash_equals($stored, $passed)) {
                http_response_code(403);
                die('Invalid CSRF token. Please go back and try again.');
            }
        }
    }

    public static function checkRateLimit(string $action, string $ip, int $limit, int $windowSeconds): void {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM rate_limits 
             WHERE action = ? AND ip_address = ? AND created_at >= datetime('now', ?)"
        );
        $stmt->execute([$action, $ip, "-$windowSeconds seconds"]);
        $count = (int)$stmt->fetchColumn();
        
        if ($count >= $limit) {
            http_response_code(429);
            die('Too many attempts. Please try again later.');
        }
    }

    public static function recordRateLimit(string $action, string $ip): void {
        Database::getConnection()->prepare("INSERT INTO rate_limits (action, ip_address) VALUES (?, ?)")
            ->execute([$action, $ip]);
    }

    public static function clearRateLimit(string $action, string $ip): void {
        Database::getConnection()->prepare("DELETE FROM rate_limits WHERE action = ? AND ip_address = ?")
            ->execute([$action, $ip]);
    }
}
