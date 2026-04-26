<?php

namespace App\Models;

class Settings {
    public string $site_name = 'Demo|shop';
    public string $currency_symbol = '£';
    public int $password_min_length = 6;
    public int $login_max_attempts = 5;
    public int $login_window_minutes = 15;
    public int $register_max_attempts = 10;
    public int $register_window_minutes = 60;
    public int $low_stock_threshold = 10;
    public int $remember_me_days = 30;
    public float $default_vat_rate = 20.0;
    public int $mobile_nav_max_top = 10;
    public int $mobile_nav_max_combined = 20;
    public string $email_from = 'noreply@shop.local';
    public string $site_url = 'http://localhost';

    /**
     * Fill settings from an associative array of key => value pairs.
     */
    public function fill(array $data): self {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                // Type casting based on default values
                $type = gettype($this->$key);
                if ($type === 'integer') {
                    $this->$key = (int)$value;
                } elseif ($type === 'double' || $type === 'float') {
                    $this->$key = (float)$value;
                } else {
                    $this->$key = (string)$value;
                }
            }
        }
        return $this;
    }
}
