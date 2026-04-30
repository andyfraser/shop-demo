<?php

namespace App\Models;

class UserAddress extends Model {
    public int $id;
    public int $user_id;
    public ?string $label = null;
    public string $name;
    public string $address;
    public string $city;
    public string $postcode;
    public string $country;
    public int|bool $is_default;
    public string $created_at;

    public function isDefault(): bool {
        return (bool)$this->is_default;
    }

    public function getFullAddress(): string {
        return "{$this->address}, {$this->city}, {$this->postcode}, {$this->country}";
    }
}
