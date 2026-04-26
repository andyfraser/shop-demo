<?php

namespace App\Models;

class User extends Model {
    public int $id;
    public string $name;
    public string $email;
    public string $password_hash;
    public string $role;
    public int|bool $is_verified;
    public ?string $verification_token = null;
    public ?string $address = null;
    public string $created_at;

    // Join fields
    public ?int $order_count = 0;

    public function isAdmin(): bool {
        return $this->role === 'admin';
    }

    public function isVerified(): bool {
        return (bool)$this->is_verified;
    }
}
