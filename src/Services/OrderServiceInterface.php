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
    public function updateStatus(int $id, string $status): void;
}
