<?php

namespace App\Services;

use App\Models\Currency;
use App\Repositories\CurrencyRepositoryInterface;
use App\Core\Request;
use App\Core\Cache\CacheInterface;

class CurrencyService implements CurrencyServiceInterface {
    private ?Currency $currentCurrency = null;
    private ?Currency $baseCurrency = null;

    public function __construct(
        private CurrencyRepositoryInterface $repository,
        private Request $request,
        private CacheInterface $cache
    ) {}

    public function getAllActive(): array {
        $cached = $this->cache->get('active_currencies');
        if ($cached !== null) {
            return $cached;
        }

        $currencies = $this->repository->getAllActive();
        $this->cache->set('active_currencies', $currencies, 3600); // Cache for 1 hour
        return $currencies;
    }

    public function getCurrentCurrency(): Currency {
        if ($this->currentCurrency !== null) {
            return $this->currentCurrency;
        }

        $code = $_SESSION['currency'] ?? null;

        if ($code) {
            // Check cache for specific currency or find in all active
            $active = $this->getAllActive();
            foreach ($active as $c) {
                if ($c->code === $code) {
                    $this->currentCurrency = $c;
                    break;
                }
            }
        }

        if (!$this->currentCurrency) {
            $this->currentCurrency = $this->getBaseCurrency();
        }

        return $this->currentCurrency;
    }

    public function getBaseCurrency(): Currency {
        if ($this->baseCurrency !== null) {
            return $this->baseCurrency;
        }

        $cached = $this->cache->get('base_currency');
        if ($cached !== null) {
            $this->baseCurrency = $cached;
            return $this->baseCurrency;
        }

        $this->baseCurrency = $this->repository->findBase();
        if ($this->baseCurrency) {
            $this->cache->set('base_currency', $this->baseCurrency, 3600);
        }
        
        return $this->baseCurrency;
    }

    public function setCurrentCurrency(string $code): void {
        // Use cache/all active to validate
        $active = $this->getAllActive();
        $found = false;
        foreach ($active as $c) {
            if ($c->code === $code) {
                $_SESSION['currency'] = $code;
                $this->currentCurrency = $c;
                $found = true;
                break;
            }
        }
    }

    public function convert(float $amount, ?string $toCode = null): float {
        $currency = null;
        if ($toCode) {
            $active = $this->getAllActive();
            foreach ($active as $c) {
                if ($c->code === $toCode) {
                    $currency = $c;
                    break;
                }
            }
        } else {
            $currency = $this->getCurrentCurrency();
        }
        
        if (!$currency) {
            return $amount;
        }

        return $currency->convertFromBase($amount);
    }

    public function clearCache(): void {
        $this->cache->delete('active_currencies');
        $this->cache->delete('base_currency');
        $this->currentCurrency = null;
        $this->baseCurrency = null;
    }
}
