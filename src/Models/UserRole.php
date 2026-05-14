<?php

namespace App\Models;

class UserRole extends Model {
    public int $id;
    public string $name;
    public string $slug;
    public ?string $description = null;
    public string $created_at;
}
