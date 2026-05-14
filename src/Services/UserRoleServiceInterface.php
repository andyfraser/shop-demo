<?php

namespace App\Services;

use App\Models\UserRole;

interface UserRoleServiceInterface {
    public function getAll(): array;
    public function findById(int $id): ?UserRole;
    public function findBySlug(string $slug): ?UserRole;
    public function save(array|UserRole $data, int $id = 0): int;
    public function delete(int $id): void;
}
