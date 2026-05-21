<?php
namespace App\Repositories;

use App\Models\DeliveryOption;

interface DeliveryRepositoryInterface {
    public function getAll(): array;
    public function getActive(float $orderTotal = 0, ?string $userRole = null): array;
    public function findById(int $id): ?DeliveryOption;
    public function save(array $data, int $id = 0): bool;
    public function delete(int $id): bool;
}
