<?php

namespace App\Models;

class ReturnOrder extends Model {
    public const STATUS_PENDING   = 'pending';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    public int $id;
    public int $order_id;
    public ?int $user_id = null;
    public string $status;
    public ?string $reason = null;
    public float $refund_amount = 0.0;
    public string $created_at;
    public string $updated_at;

    /** @var ReturnItem[] */
    public array $items = [];

    // Join fields
    public ?string $customer_name = null;
    public ?string $order_formatted_id = null;

    public function getStatusBadgeClass(): string {
        return match($this->status) {
            self::STATUS_PENDING   => 'badge-warning',
            self::STATUS_APPROVED  => 'badge-info',
            self::STATUS_COMPLETED => 'badge-success',
            self::STATUS_REJECTED  => 'badge-danger',
            default                => 'badge-neutral',
        };
    }
}
