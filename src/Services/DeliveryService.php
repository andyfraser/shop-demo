<?php
namespace App\Services;

use App\Core\Database;

class DeliveryService {
    public static function all(): array {
        return Database::getConnection()
            ->query("SELECT * FROM delivery_options ORDER BY price ASC")
            ->fetchAll();
    }

    public static function active(float $orderTotal = 0): array {
        return Database::getConnection()
            ->query("SELECT * FROM delivery_options WHERE active = 1 AND min_order_total <= $orderTotal ORDER BY price ASC")
            ->fetchAll();
    }

    public static function get(int $id): ?array {
        $stmt = Database::getConnection()->prepare("SELECT * FROM delivery_options WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function save(array $data): bool {
        $db = Database::getConnection();
        if (isset($data['id']) && $data['id']) {
            $stmt = $db->prepare("UPDATE delivery_options SET name = ?, price = ?, active = ?, min_order_total = ? WHERE id = ?");
            return $stmt->execute([$data['name'], $data['price'], $data['active'] ?? 0, $data['min_order_total'] ?? 0, $data['id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO delivery_options (name, price, active, min_order_total) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$data['name'], $data['price'], $data['active'] ?? 0, $data['min_order_total'] ?? 0]);
        }
    }

    public static function delete(int $id): bool {
        $stmt = Database::getConnection()->prepare("DELETE FROM delivery_options WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
