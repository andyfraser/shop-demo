<?php
namespace App\Repositories;

interface SettingsRepositoryInterface {
    public function getAll(): array;
    public function set(string $key, mixed $value): void;
}
