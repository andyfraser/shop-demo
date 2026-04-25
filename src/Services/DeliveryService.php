<?php
namespace App\Services;

class DeliveryService {
    private \PDO $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    public function all(): array {
        return $this->db
            ->query("SELECT * FROM delivery_options ORDER BY price ASC")
            ->fetchAll();
    }

    public function active(float $orderTotal = 0): array {
        return $this->db
            ->query("SELECT * FROM delivery_options WHERE active = 1 AND min_order_total <= $orderTotal ORDER BY price ASC")
            ->fetchAll();
    }

    public function get(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM delivery_options WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function save(array $data): bool {
        if (isset($data['id']) && $data['id']) {
            $stmt = $this->db->prepare("UPDATE delivery_options SET name = ?, price = ?, active = ?, min_order_total = ? WHERE id = ?");
            return $stmt->execute([$data['name'], $data['price'], $data['active'] ?? 0, $data['min_order_total'] ?? 0, $data['id']]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO delivery_options (name, price, active, min_order_total) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$data['name'], $data['price'], $data['active'] ?? 0, $data['min_order_total'] ?? 0]);
        }
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM delivery_options WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
