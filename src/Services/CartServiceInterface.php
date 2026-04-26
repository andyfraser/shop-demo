<?php
namespace App\Services;

interface CartServiceInterface {
    public function get(): array;
    public function add(int $productId, int $qty = 1): void;
    public function remove(int $productId): void;
    public function update(int $productId, int $qty): void;
    public function clear(): void;
    public function count(): int;
    public function items(): array;
    public function total(): float;
    public function totalVat(): float;
}
