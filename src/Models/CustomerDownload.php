<?php

namespace App\Models;

class CustomerDownload extends Model {
    public int $id;
    public ?int $user_id = null;
    public int $order_item_id;
    public int $product_id;
    public ?int $variant_id = null;
    public string $download_token;
    public int $download_count = 0;
    public ?int $max_downloads = null;
    public ?string $expires_at = null;
    public string $created_at;

    // Join fields
    public ?string $customer_email = null;
    public ?int $order_user_id = null;
}
