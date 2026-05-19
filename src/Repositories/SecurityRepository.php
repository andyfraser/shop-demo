<?php
namespace App\Repositories;

use PDO;

class SecurityRepository implements SecurityRepositoryInterface {
    public function __construct(private PDO $db) {}

    public function countRateLimits(string $action, string $ip, string $since): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM rate_limits 
             WHERE action = ? AND ip_address = ? AND created_at >= ?"
        );
        $stmt->execute([$action, $ip, $since]);
        return (int)$stmt->fetchColumn();
    }

    public function recordRateLimit(string $action, string $ip): void {
        $this->db->prepare("INSERT INTO rate_limits (action, ip_address, created_at) VALUES (?, ?, ?)")
            ->execute([$action, $ip, date('Y-m-d H:i:s')]);
    }

    public function clearRateLimit(string $action, string $ip): void {
        $this->db->prepare("DELETE FROM rate_limits WHERE action = ? AND ip_address = ?")
            ->execute([$action, $ip]);
    }
}
