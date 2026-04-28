<?php
namespace App\Services;

use App\Models\Order;

interface OrderServiceInterface {
    public function create(array $orderData, array $items): int;
    public function findById(int $id): ?Order;
    public function getItems(int $orderId): array;
    public function getAllForAdmin(string $status = ''): array;
    public function getForUser(int $userId): array;
    public function countAll(): int;
    public function getTotalRevenue(): float;
    public function getRecentOrders(int $limit = 10): array;
    public function updateStatus(int $id, string $status, ?int $userId = null, string $notes = ''): void;
    public function addHistoryEntry(int $orderId, string $status, string $notes = '', ?int $userId = null): void;
    public function updatePaymentInfo(int $id, string $method, string $status, ?string $transactionId = null): void;
    public function cancelOrder(int $id, string $reason = '', ?int $userId = null): bool;
    public function getStatusHistory(int $orderId): array;
}
