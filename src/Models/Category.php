<?php

namespace App\Models;

class Category extends Model {
    public int $id;
    public string $name;
    public string $slug;
    public ?int $parent_id = null;
    public ?string $description = null;
    public ?string $icon = null;
    public string $created_at;

    // Join fields (explicitly defined)
    public ?string $parent_name = null;
    public ?int $product_count = 0;

    /** @var Category[] */
    public array $children = [];
}
