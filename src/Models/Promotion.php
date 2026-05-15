<?php

namespace App\Models;

class Promotion extends Model {
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED_AMOUNT = 'fixed_amount';
    public const TYPE_FREE_SHIPPING = 'free_shipping';
    public const TYPE_BUY_X_GET_Y = 'buy_x_get_y';

    public const TARGET_ORDER = 'order';
    public const TARGET_PRODUCT = 'product';
    public const TARGET_CATEGORY = 'category';

    public const ROLE_FIRST_TIME = 'first_time';

    public int $id;
    public string $name;
    public ?string $description = null;
    public ?string $code = null;
    public string $type;
    public float $value;
    public ?int $buy_qty = null;
    public ?int $get_qty = null;
    public string $target_type;
    public float $min_order_amount;
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?int $usage_limit = null;
    public ?int $usage_limit_per_user = null;
    public int $priority = 0;
    public int|bool $stackable = 0;
    public ?string $target_role = null;
    public int $used_count;
    public ?int $user_usage_count = null;
    public int|bool $active;
    public string $created_at;

    /**
     * @var int[] IDs of target products or categories.
     */
    public array $target_ids = [];

    /**
     * @var int[] IDs of excluded products or categories.
     */
    public array $excluded_ids = [];

    /**
     * @var array List of tiers [{min_amount, value}]
     */
    public array $tiers = [];

    /**
     * @var string[] List of additional promo codes
     */
    public array $additional_codes = [];

    /**
     * Check if the promotion is live (enabled, within dates, and under usage limit),
     * regardless of user-specific targeting.
     */
    public function isLive(): bool {
        if (!$this->active) {
            return false;
        }

        $now = time();

        if ($this->start_date && strtotime($this->start_date) > $now) {
            return false;
        }

        if ($this->end_date && strtotime($this->end_date) < $now) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Check if the promotion is currently active for a specific user.
     */
    public function isActive(?User $user = null, bool $isFirstOrder = false): bool {
        if (!$this->isLive()) {
            return false;
        }

        if ($this->target_role) {
            if ($this->target_role === self::ROLE_FIRST_TIME) {
                if (!$isFirstOrder) {
                    return false;
                }
            } elseif (!$user || $user->role !== $this->target_role) {
                return false;
            }
        }

        if ($user && $this->usage_limit_per_user !== null && isset($this->user_usage_count)) {
            if ($this->user_usage_count >= $this->usage_limit_per_user) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the promotion is automatic (no code required).
     */
    public function isAutomatic(): bool {
        return empty($this->code);
    }
}
