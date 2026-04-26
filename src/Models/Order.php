<?php

namespace App\Models;

class Order extends Model {
    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_SHIPPED   = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public int $id;
    public ?int $user_id = null;
    public string $status;
    public float $total;
    public float $total_vat_amount;
    public ?string $shipping_address = null;
    public ?string $notes = null;
    public ?string $delivery_method = null;
    public float $delivery_cost = 0.0;
    public ?string $customer_email = null;
    public ?string $customer_name = null;
    public string $created_at;

    // Join fields
    public ?string $user_name = null;
    public ?string $user_email = null;
    public ?int $item_count = 0;

    /** @var OrderItem[] */
    public array $items = [];

    public function canBeCancelled(): bool {
        return $this->status === self::STATUS_PENDING;
    }

    public function getFormattedId(): string {
        return '#' . str_pad((string)$this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getStatusBadgeClass(): string {
        return match($this->status) {
            self::STATUS_PENDING   => 'badge-warning',
            self::STATUS_CONFIRMED => 'badge-info',
            self::STATUS_SHIPPED   => 'badge-info',
            self::STATUS_DELIVERED => 'badge-success',
            self::STATUS_CANCELLED => 'badge-danger',
            default                => 'badge-neutral',
        };
    }
}
