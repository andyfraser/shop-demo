<?php

namespace App\Models;

class AttributeValue extends Model {
    public int $id;
    public int $attribute_id;
    public string $value;
    public int $sort_order;
    public string $created_at;
}
