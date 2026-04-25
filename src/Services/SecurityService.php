<?php
namespace App\Services;

use App\Core\Database;

class SecurityService {
    private \PDO $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    public function csrfToken(): string {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . h($this->csrfToken()) . '">';
    }

    public function verifyCsrf(): void {
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

    public function checkRateLimit(string $action, string $ip, int $limit, int $windowSeconds): void {
        $since = date('Y-m-d H:i:s', time() - $windowSeconds);
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM rate_limits 
             WHERE action = ? AND ip_address = ? AND created_at >= ?"
        );
        $stmt->execute([$action, $ip, $since]);
        $count = (int)$stmt->fetchColumn();
        
        if ($count >= $limit) {
            http_response_code(429);
            die('Too many attempts. Please try again later.');
        }
    }

    public function recordRateLimit(string $action, string $ip): void {
        $this->db->prepare("INSERT INTO rate_limits (action, ip_address) VALUES (?, ?)")
            ->execute([$action, $ip]);
    }

    public function clearRateLimit(string $action, string $ip): void {
        $this->db->prepare("DELETE FROM rate_limits WHERE action = ? AND ip_address = ?")
            ->execute([$action, $ip]);
    }
}
