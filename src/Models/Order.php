<?php

namespace App\Models;

class Order extends Model {
    public const STATUS_PENDING        = 'pending';
    public const STATUS_CONFIRMED      = 'confirmed';
    public const STATUS_SHIPPED        = 'shipped';
    public const STATUS_DELIVERED      = 'delivered';
    public const STATUS_CANCELLED      = 'cancelled';
    public const STATUS_RETURNING      = 'returning';
    public const STATUS_NOT_REFUNDED   = 'not refunded';
    public const STATUS_FULLY_REFUNDED = 'fully refunded';
    public const STATUS_PARTIAL_REFUND = 'partial refund';

    public int $id;
    public ?int $user_id = null;
    public string $status;
    public float $total;
    public float $total_vat_amount;
    public ?string $shipping_address = null;
    public ?string $notes = null;
    public ?string $delivery_method = null;
    public float $delivery_cost = 0.0;
    public bool $delivery_refunded = false;
    public ?string $customer_email = null;
    public ?string $customer_name = null;
    public ?string $payment_method = null;
    public string $payment_status = 'pending';
    public ?string $payment_transaction_id = null;
    public ?string $refund_status = null;
    public float $refunded_amount = 0.0;
    public ?int $promotion_id = null;
    public float $discount_amount = 0.0;
    public ?string $applied_promo_name = null;
    public ?string $applied_promo_code = null;
    public string $created_at;

    // Join fields
    public ?string $user_name = null;
    public ?string $user_email = null;
    public ?string $promotion_name = null;
    public ?int $item_count = 0;

    /** @var OrderItem[] */
    public array $items = [];

    /** @var array List of all applied promotions [{promotion_id, name, discount_amount, promo_code}] */
    public array $applied_promotions = [];

    public function canBeCancelled(): bool {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }

    public function canBeReturned(): bool {
        return in_array($this->status, [
            self::STATUS_DELIVERED,
            self::STATUS_RETURNING,
            self::STATUS_NOT_REFUNDED,
            self::STATUS_PARTIAL_REFUND
        ]);
    }

    public function getFormattedId(): string {
        return '#' . str_pad((string)$this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getStatusBadgeClass(): string {
        return match($this->status) {
            self::STATUS_PENDING        => 'badge-warning',
            self::STATUS_CONFIRMED      => 'badge-info',
            self::STATUS_SHIPPED        => 'badge-info',
            self::STATUS_DELIVERED      => 'badge-success',
            self::STATUS_CANCELLED      => 'badge-danger',
            self::STATUS_RETURNING      => 'badge-warning',
            self::STATUS_NOT_REFUNDED   => 'badge-danger',
            self::STATUS_FULLY_REFUNDED => 'badge-success',
            self::STATUS_PARTIAL_REFUND => 'badge-success',
            default                     => 'badge-neutral',
        };
    }
}
