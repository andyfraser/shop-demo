<?php
namespace App\Services;

class VatService implements VatServiceInterface {
    /**
     * Calculate VAT amount from a gross price (VAT inclusive).
     * Formula: VAT Amount = Gross Amount * (VAT Rate / (100 + VAT Rate))
     */
    public function calculateVatFromGross(float $grossAmount, float $vatRate): float {
        if ($vatRate <= 0) return 0.0;
        return $grossAmount * ($vatRate / (100 + $vatRate));
    }

    /**
     * Calculate gross price from a net price (VAT exclusive).
     * Formula: Gross Amount = Net Amount * (1 + (VAT Rate / 100))
     */
    public function getGrossFromNet(float $netAmount, float $vatRate): float {
        return $netAmount * (1 + ($vatRate / 100));
    }
}
