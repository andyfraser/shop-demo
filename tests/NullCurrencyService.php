<?php

namespace Tests;

use App\Services\CurrencyServiceInterface;
use App\Models\Currency;

class NullCurrencyService implements CurrencyServiceInterface {
    public function getAllActive(): array { return []; }
    public function getCurrentCurrency(): Currency { 
        $c = new Currency(new NullLogger());
        $c->code = 'USD';
        $c->symbol = '$';
        $c->exchange_rate = 1.0;
        return $c;
    }
    public function getBaseCurrency(): Currency { return $this->getCurrentCurrency(); }
    public function setCurrentCurrency(string $code): void {}
    public function convert(float $amount, ?string $toCode = null): float { return $amount; }
    public function clearCache(): void {}
}
