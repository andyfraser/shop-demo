<?php

namespace App\Services;

use App\Models\DeliveryOption;

class DeliveryService {
    public function __construct(private \PDO $db) {}

    public function all(): array {
        return $this->db
            ->query("SELECT * FROM delivery_options ORDER BY price ASC")
            ->fetchAll(\PDO::FETCH_CLASS, DeliveryOption::class);
    }

    public function active(float $orderTotal = 0): array {
        return $this->db
            ->query("SELECT * FROM delivery_options WHERE active = 1 AND min_order_total <= $orderTotal ORDER BY price ASC")
            ->fetchAll(\PDO::FETCH_CLASS, DeliveryOption::class);
    }

    public function get(int $id): ?DeliveryOption {
        $stmt = $this->db->prepare("SELECT * FROM delivery_options WHERE id = ?");
        $stmt->setFetchMode(\PDO::FETCH_CLASS, DeliveryOption::class);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function save(array|DeliveryOption $data): bool {
        if (is_object($data)) {
            $data = [
                'id'              => $data->id,
                'name'            => $data->name,
                'price'           => $data->price,
                'active'          => $data->active,
                'min_order_total' => $data->min_order_total,
            ];
        }

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
