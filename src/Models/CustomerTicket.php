<?php

namespace App\Models;

class CustomerTicket extends Model {
    public int $id;
    public ?int $user_id = null;
    public int $order_item_id;
    public string $ticket_code;
    public string $created_at;
}
