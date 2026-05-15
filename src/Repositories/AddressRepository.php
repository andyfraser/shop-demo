<?php
namespace App\Repositories;

use App\Models\UserAddress;
use Psr\Log\LoggerInterface;

class AddressRepository implements AddressRepositoryInterface {
    public function __construct(
        private \PDO $db,
        private LoggerInterface $logger
    ) {}

    public function getByUserId(int $userId): array {
        $stmt = $this->db->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, UserAddress::class, [$this->logger]);
    }

    public function findById(int $id): ?UserAddress {
        $stmt = $this->db->prepare("SELECT * FROM user_addresses WHERE id = ?");
        $stmt->setFetchMode(\PDO::FETCH_CLASS, UserAddress::class, [$this->logger]);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function save(int $userId, array $data, int $id = 0): int {
        $params = [
            $userId, $data['label'] ?? null, $data['name'], $data['address'], $data['city'], 
            $data['postcode'], $data['country'], (int)($data['is_default'] ?? 0)
        ];

        if ($id) {
            $this->db->prepare(
                "UPDATE user_addresses SET user_id=?, label=?, name=?, address=?, city=?, postcode=?, country=?, is_default=? WHERE id=?"
            )->execute([...$params, $id]);
            return $id;
        } else {
            $this->db->prepare(
                "INSERT INTO user_addresses (user_id, label, name, address, city, postcode, country, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute($params);
            return (int)$this->db->lastInsertId();
        }
    }

    public function delete(int $id, int $userId): bool {
        $stmt = $this->db->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public function setDefault(int $id, int $userId): bool {
        $this->db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
        $stmt = $this->db->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}
