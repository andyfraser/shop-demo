<?php

namespace App\Models;

class Cart extends Model {
    public int $id;
    public ?int $user_id = null;
    public string $session_id;
    public string $last_activity;
    public ?string $recovery_email_sent_at = null;

    /** @var CartItem[] */
    public array $items = [];

    public function getItemCount(): int {
        return array_sum(array_map(fn($item) => $item->qty, $this->items));
    }

    public function getTotal(): float {
        return array_sum(array_map(fn($item) => $item->getSubtotal(), $this->items));
    }

    public function getTotalVat(): float {
        return array_sum(array_map(fn($item) => $item->getVatAmount(), $this->items));
    }
}
