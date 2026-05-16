<?php

namespace App\Services;

use App\Models\ReturnOrder;

interface ReturnServiceInterface {
    public function createReturnRequest(int $orderId, int $userId, array $items, string $reason): int;
    public function findById(int $id): ?ReturnOrder;
    public function getForOrder(int $orderId): array;
    public function getForUser(int $userId): array;
    public function getAllForAdmin(): array;
    public function approveReturn(int $id, bool $refundDelivery = false, ?int $userId = null): bool;
    public function rejectReturn(int $id, string $reason, ?int $userId = null): bool;
}
