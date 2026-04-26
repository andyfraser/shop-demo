<?php

namespace App\Models;

class User {
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

    public function fill(array $data): self {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    public function isAdmin(): bool {
        return $this->role === 'admin';
    }

    public function isVerified(): bool {
        return (bool)$this->is_verified;
    }

    public function getGravatarUrl(int $size = 80): string {
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/$hash?s=$size&d=mp";
    }
}
