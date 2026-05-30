<?php
namespace App\Repositories;

use App\Models\User;
use Psr\Log\LoggerInterface;

class AuthRepository implements AuthRepositoryInterface {
    public function __construct(
        private \PDO $db,
        private LoggerInterface $logger
    ) {}

    public function findUserById(int $userId): ?User {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, User::class, [$this->logger]);
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function findUserByRememberToken(string $token): ?User {
        $stmt = $this->db->prepare(
            "SELECT u.*
             FROM remember_tokens rt
             JOIN users u ON u.id = rt.user_id
             WHERE rt.token = ? AND rt.expires_at > ?"
        );
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, User::class, [$this->logger]);
        $stmt->execute([$token, time()]);
        return $stmt->fetch() ?: null;
    }

    public function setRememberToken(int $userId, string $token, int $expires, ?string $oldToken = null): void {
        if ($oldToken) {
            $stmt = $this->db->prepare("SELECT id FROM remember_tokens WHERE token = ?");
            $stmt->execute([$oldToken]);
            $id = $stmt->fetchColumn();

            if ($id) {
                $this->db->prepare("UPDATE remember_tokens SET token = ?, expires_at = ? WHERE id = ?")
                    ->execute([$token, $expires, $id]);
                return;
            }
        }

        $this->db->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)")
            ->execute([$userId, $token, $expires]);
    }

    public function clearRememberToken(string $token): void {
        $this->db->prepare("DELETE FROM remember_tokens WHERE token = ?")
            ->execute([$token]);
    }
}
