<?php

namespace App\Models;

class Attribute extends Model {
    public int $id;
    public string $name;
    public string $created_at;

    /** @var AttributeValue[] */
    public array $values = [];
}
