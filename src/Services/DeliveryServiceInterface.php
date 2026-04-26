<?php
namespace App\Services;

use App\Models\DeliveryOption;

interface DeliveryServiceInterface {
    public function all(): array;
    public function active(float $orderTotal = 0): array;
    public function get(int $id): ?DeliveryOption;
    public function save(array|DeliveryOption $data): bool;
    public function delete(int $id): bool;
}
