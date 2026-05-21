<?php

namespace App\Models;

class GiftCard extends Model {
    public int $id;
    public string $code;
    public float $initial_amount;
    public float $remaining_amount;
    public string $recipient_email;
    public ?string $sender_name = null;
    public ?string $message = null;
    public ?int $order_item_id = null;
    public int|bool $is_active = 1;
    public string $created_at;
}
