<?php
namespace App\Repositories;

use App\Models\ReturnOrder;

interface ReturnRepositoryInterface {
    public function createReturnRequest(int $orderId, ?int $userId, string $reason, array $items): int;
    public function findById(int $id): ?ReturnOrder;
    public function getForUser(int $userId): array;
    public function getForOrder(int $orderId): array;
    public function getAllForAdmin(): array;
    public function updateStatus(int $id, string $status, ?string $reason = null, ?float $refundAmount = null): void;
    public function getPendingReturnCount(int $orderId, int $excludeReturnId): int;
    public function replenishStock(array $items): void;
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollBack(): void;
    public function inTransaction(): bool;
}
