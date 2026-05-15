<?php
namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;

interface OrderRepositoryInterface {
    public function create(array $orderData, array $items): int;
    public function findById(int $id): ?Order;
    public function getAppliedPromotions(int $orderId): array;
    public function getItems(int $orderId): array;
    public function getAllForAdmin(string $status = ''): array;
    public function getForUser(int $userId): array;
    public function hasOrders(int $userId): bool;
    public function countAll(): int;
    public function getTotalRevenue(): float;
    public function getRecentOrders(int $limit = 10): array;
    public function updateStatus(int $id, string $status): void;
    public function addHistoryEntry(int $orderId, string $status, string $notes = '', ?int $userId = null): void;
    public function getStatusHistory(int $orderId): array;
    public function updatePaymentInfo(int $id, string $method, string $status, ?string $transactionId = null): void;
    public function updateRefundInfo(int $id, string $status, float $amount, bool $deliveryRefunded = false): void;
    public function replenishStock(array $items): void;
    public function inTransaction(): bool;
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollBack(): void;
}
