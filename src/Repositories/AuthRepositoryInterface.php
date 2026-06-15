<?php
namespace App\Repositories;

use App\Models\User;

interface AuthRepositoryInterface {
    public function findUserById(int $userId): ?User;
    public function findUserByRememberToken(string $token): ?User;
    public function setRememberToken(int $userId, string $token, int $expires, ?string $oldToken = null): void;
    public function clearRememberToken(string $token): void;
    public function saveApiToken(int $userId, string $tokenHash, string $createdAt, ?string $expiresAt): void;
    public function findUserByApiTokenHash(string $tokenHash): ?User;
    public function deleteApiTokenHash(string $tokenHash): bool;
    public function cleanupExpiredRememberTokens(int $now): int;
}
