<?php

namespace App\Repositories;

use App\Models\AuditLog;
use PDO;

class AuditLogRepository implements AuditLogRepositoryInterface {
    public function __construct(private PDO $db) {}

    public function log(array $data): void {
        $sql = "INSERT INTO audit_logs (user_id, action, resource_type, resource_id, details, ip_address) 
                VALUES (:user_id, :action, :resource_type, :resource_id, :details, :ip_address)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id'       => $data['user_id'] ?? null,
            'action'        => $data['action'],
            'resource_type' => $data['resource_type'] ?? null,
            'resource_id'   => $data['resource_id'] ?? null,
            'details'       => $data['details'] ?? null,
            'ip_address'    => $data['ip_address'] ?? null,
        ]);
    }

    public function find(array $criteria = [], int $limit = 50, int $offset = 0): array {
        $where = [];
        $params = [];

        if (!empty($criteria['user_id'])) {
            $where[] = "user_id = :user_id";
            $params['user_id'] = $criteria['user_id'];
        }

        if (!empty($criteria['action'])) {
            $where[] = "action = :action";
            $params['action'] = $criteria['action'];
        }

        $sql = "SELECT * FROM audit_logs";
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $logs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $log = new AuditLog();
            $log->fill($row);
            $logs[] = $log;
        }

        return $logs;
    }

    public function count(array $criteria = []): int {
        $where = [];
        $params = [];

        if (!empty($criteria['user_id'])) {
            $where[] = "user_id = :user_id";
            $params['user_id'] = $criteria['user_id'];
        }

        if (!empty($criteria['action'])) {
            $where[] = "action = :action";
            $params['action'] = $criteria['action'];
        }

        $sql = "SELECT COUNT(*) FROM audit_logs";
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    }
}
