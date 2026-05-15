<?php
namespace App\Repositories;

use App\Models\DeliveryOption;
use Psr\Log\LoggerInterface;
use PDO;

class DeliveryRepository implements DeliveryRepositoryInterface {
    public function __construct(
        private PDO $db,
        private LoggerInterface $logger
    ) {}

    public function getAll(): array {
        return $this->db
            ->query("SELECT * FROM delivery_options ORDER BY price ASC")
            ->fetchAll(PDO::FETCH_CLASS, DeliveryOption::class, [$this->logger]);
    }

    public function getActive(float $orderTotal = 0): array {
        $stmt = $this->db->prepare("SELECT * FROM delivery_options WHERE active = 1 AND min_order_total <= ? ORDER BY price ASC");
        $stmt->execute([$orderTotal]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, DeliveryOption::class, [$this->logger]);
    }

    public function findById(int $id): ?DeliveryOption {
        $stmt = $this->db->prepare("SELECT * FROM delivery_options WHERE id = ?");
        $stmt->setFetchMode(PDO::FETCH_CLASS, DeliveryOption::class, [$this->logger]);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function save(array $data, int $id = 0): bool {
        if ($id) {
            $stmt = $this->db->prepare("UPDATE delivery_options SET name = ?, price = ?, active = ?, min_order_total = ? WHERE id = ?");
            return $stmt->execute([$data['name'], $data['price'], $data['active'] ?? 0, $data['min_order_total'] ?? 0, $id]);
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
