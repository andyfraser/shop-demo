<?php
namespace App\Services;

use App\Models\Settings;

interface SettingsServiceInterface {
    public function getSettings(): Settings;
    public function get(string $key): mixed;
    public function all(): array;
    public function set(string $key, mixed $value): void;
}
