<?php

namespace App\Models;

class OrderStatusHistory extends Model {
    public int $id;
    public int $order_id;
    public string $status;
    public ?string $notes = null;
    public ?int $created_by_user_id = null;
    public string $created_at;

    // Join fields
    public ?string $user_name = null;

    public function getStatusBadgeClass(): string {
        return match ($this->status) {
            Order::STATUS_PENDING        => 'badge-warning',
            Order::STATUS_PAID           => 'badge-success',
            Order::STATUS_CONFIRMED      => 'badge-info',
            Order::STATUS_SHIPPED        => 'badge-primary',
            Order::STATUS_DELIVERED      => 'badge-success',
            Order::STATUS_CANCELLED      => 'badge-danger',
            Order::STATUS_RETURNING      => 'badge-warning',
            Order::STATUS_NOT_REFUNDED   => 'badge-danger',
            Order::STATUS_FULLY_REFUNDED => 'badge-success',
            Order::STATUS_PARTIAL_REFUND => 'badge-info',
            default                      => 'badge-secondary',
        };
    }
}
