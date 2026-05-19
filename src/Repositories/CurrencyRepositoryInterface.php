<?php

namespace App\Repositories;

use App\Models\Currency;

interface CurrencyRepositoryInterface {
    public function getAllActive(): array;
    public function getAll(): array;
    public function findByCode(string $code): ?Currency;
    public function findBase(): ?Currency;
    public function findById(int $id): ?Currency;
    public function save(array|Currency $data, int $id = 0): int;
}
