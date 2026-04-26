<?php
namespace App\Services;

interface VatServiceInterface {
    public function calculateVatFromGross(float $grossAmount, float $vatRate): float;
    public function getGrossFromNet(float $netAmount, float $vatRate): float;
}
