<?php
namespace App\Services;

use App\Models\User;

interface UserServiceInterface {
    public function getAll(): array;
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function findByVerificationToken(string $token): ?User;
    public function countByRole(string $role): int;
    public function countNonAdmins(): int;
    public function save(array|User $data, int $id = 0): int;
    public function updateAddress(int $id, string $address): void;
    public function delete(int $id): void;
}
