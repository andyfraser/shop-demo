<?php

namespace App\Services;

use App\Models\Currency;

interface CurrencyServiceInterface {
    public function getAllActive(): array;
    public function getCurrentCurrency(): Currency;
    public function getBaseCurrency(): Currency;
    public function setCurrentCurrency(string $code): void;
    public function convert(float $amount, ?string $toCode = null): float;
    public function clearCache(): void;
}
