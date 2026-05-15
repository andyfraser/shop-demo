<?php
namespace App\Repositories;

use App\Models\User;
use Psr\Log\LoggerInterface;

class UserRepository implements UserRepositoryInterface {
    public function __construct(
        private \PDO $db,
        private LoggerInterface $logger
    ) {}

    public function getAll(): array {
        return $this->db->query(
            "SELECT u.*, COUNT(o.id) as order_count
             FROM users u
             LEFT JOIN orders o ON o.user_id = u.id
             GROUP BY u.id
             ORDER BY u.id DESC"
        )->fetchAll(\PDO::FETCH_CLASS, User::class, [$this->logger]);
    }

    public function findById(int $id): ?User {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->setFetchMode(\PDO::FETCH_CLASS, User::class, [$this->logger]);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?User {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->setFetchMode(\PDO::FETCH_CLASS, User::class, [$this->logger]);
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function findByVerificationToken(string $token): ?User {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE verification_token = ?");
        $stmt->setFetchMode(\PDO::FETCH_CLASS, User::class, [$this->logger]);
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public function countByRole(string $role): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role = ?");
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    }

    public function countNonAdmins(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
        return (int)$stmt->fetchColumn();
    }

    public function save(array|User $data, int $id = 0): int {
        if (is_object($data)) {
            $data = [
                'name'               => $data->name,
                'email'              => $data->email,
                'password_hash'      => $data->password_hash,
                'role'               => $data->role,
                'is_verified'        => $data->is_verified,
                'verification_token' => $data->verification_token,
                'address'            => $data->address,
            ];
        }

        if ($id) {
            $this->db->prepare(
                "UPDATE users SET name=?, email=?, password_hash=?, role=?, is_verified=?, verification_token=?, address=? WHERE id=?"
            )->execute([
                $data['name'], $data['email'], $data['password_hash'], $data['role'],
                (int)$data['is_verified'], $data['verification_token'], $data['address'], $id
            ]);
            return $id;
        } else {
            $this->db->prepare(
                "INSERT INTO users (name, email, password_hash, role, is_verified, verification_token, address)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                $data['name'], $data['email'], $data['password_hash'], $data['role'],
                (int)$data['is_verified'], $data['verification_token'], $data['address']
            ]);
            return (int)$this->db->lastInsertId();
        }
    }

    public function updateAddress(int $id, string $address): void {
        $stmt = $this->db->prepare("UPDATE users SET address = ? WHERE id = ?");
        $stmt->execute([$address, $id]);
    }

    public function delete(int $id): void {
        $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    }
}
