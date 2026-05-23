<?php

namespace App\Repositories;

use PDO;

class JobRepository implements JobRepositoryInterface {
    public function __construct(private PDO $db) {}

    public function create(array $data): int {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['available_at'] = $data['available_at'] ?? $data['created_at'];
        
        $fields = array_keys($data);
        $placeholders = array_map(fn($f) => ":{$f}", $fields);
        
        $sql = sprintf(
            "INSERT INTO jobs (%s) VALUES (%s)",
            implode(', ', $fields),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        
        return (int)$this->db->lastInsertId();
    }

    public function findPending(int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT * FROM jobs 
            WHERE status = 'pending'
                AND available_at <= :now
            ORDER BY created_at ASC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':now', date('Y-m-d H:i:s'));
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $data): bool {
        $sets = [];
        foreach (array_keys($data) as $field) {
            $sets[] = "{$field} = :{$field}";
        }
        
        $sql = sprintf(
            "UPDATE jobs SET %s WHERE id = :id",
            implode(', ', $sets)
        );

        $stmt = $this->db->prepare($sql);
        $data['id'] = $id;
        
        return $stmt->execute($data);
    }

    public function claim(int $id, string $startedAt, int $attempts): bool {
        $stmt = $this->db->prepare("
            UPDATE jobs 
            SET status = 'running', 
                started_at = :started_at, 
                attempts = :attempts 
            WHERE id = :id AND status = 'pending'
        ");
        
        $stmt->execute([
            'id' => $id,
            'started_at' => $startedAt,
            'attempts' => $attempts
        ]);
        
        return $stmt->rowCount() > 0;
    }

    public function deleteByStatusAndAge(string $status, int $hours): int {
        $stmt = $this->db->prepare("
            DELETE FROM jobs 
            WHERE status = :status 
              AND finished_at <= :cutoff
        ");
        
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        $stmt->execute([
            'status' => $status,
            'cutoff' => $cutoff
        ]);
        
        return $stmt->rowCount();
    }
}
