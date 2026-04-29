<?php
namespace App\Services;

interface AddressServiceInterface {
    public function getByUserId(int $userId): array;
    public function findById(int $id): ?array;
    public function save(int $userId, array $data, int $id = 0): int;
    public function delete(int $id, int $userId): bool;
    public function setDefault(int $id, int $userId): bool;
}
