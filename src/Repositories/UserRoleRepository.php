<?php
namespace App\Repositories;

use App\Models\UserRole;
use Psr\Log\LoggerInterface;

class UserRoleRepository implements UserRoleRepositoryInterface {
    public function __construct(
        private \PDO $db,
        private LoggerInterface $logger
    ) {}

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM user_roles ORDER BY name ASC");
        return $stmt->fetchAll(\PDO::FETCH_CLASS, UserRole::class, [$this->logger]);
    }

    public function findById(int $id): ?UserRole {
        $stmt = $this->db->prepare("SELECT * FROM user_roles WHERE id = ?");
        $stmt->setFetchMode(\PDO::FETCH_CLASS, UserRole::class, [$this->logger]);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug): ?UserRole {
        $stmt = $this->db->prepare("SELECT * FROM user_roles WHERE slug = ?");
        $stmt->setFetchMode(\PDO::FETCH_CLASS, UserRole::class, [$this->logger]);
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function save(array|UserRole $data, int $id = 0): int {
        if (is_object($data)) {
            $data = [
                'name'        => $data->name,
                'slug'        => $data->slug,
                'description' => $data->description,
            ];
        }

        if ($id) {
            $this->db->prepare(
                "UPDATE user_roles SET name = ?, slug = ?, description = ? WHERE id = ?"
            )->execute([$data['name'], $data['slug'], $data['description'], $id]);
            return $id;
        } else {
            $this->db->prepare(
                "INSERT INTO user_roles (name, slug, description) VALUES (?, ?, ?)"
            )->execute([$data['name'], $data['slug'], $data['description']]);
            return (int)$this->db->lastInsertId();
        }
    }

    public function delete(int $id): void {
        $this->db->prepare("DELETE FROM user_roles WHERE id = ?")->execute([$id]);
    }
}
