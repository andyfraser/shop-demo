<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\VatService;

class VatTest extends TestCase {
    private VatService $vatService;

    public function setUp(): void {
        $this->vatService = new VatService();
    }

    public function testVatExtraction() {
        // Formula: VAT Amount = Gross Amount * (VAT Rate / (100 + VAT Rate))
        
        $rate = 20.0;
        $gross = 120.0;
        $expectedVat = 20.0;
        $actualVat = $this->vatService->calculateVatFromGross($gross, $rate);
        $this->assertEquals($expectedVat, round($actualVat, 2), "20% VAT on 120 should be 20");

        $rate = 5.0;
        $gross = 105.0;
        $expectedVat = 5.0;
        $actualVat = $this->vatService->calculateVatFromGross($gross, $rate);
        $this->assertEquals($expectedVat, round($actualVat, 2), "5% VAT on 105 should be 5");

        $rate = 0.0;
        $gross = 100.0;
        $expectedVat = 0.0;
        $actualVat = $this->vatService->calculateVatFromGross($gross, $rate);
        $this->assertEquals($expectedVat, round($actualVat, 2), "0% VAT on 100 should be 0");
    }

    public function testGrossCalculation() {
        $rate = 20.0;
        $net = 100.0;
        $expectedGross = 120.0;
        $actualGross = $this->vatService->getGrossFromNet($net, $rate);
        $this->assertEquals($expectedGross, $actualGross, "Net 100 + 20% VAT should be 120");
    }

    protected function assertEquals($expected, $actual, string $message = ''): void {
        $this->assertions++;
        // Use a small delta for float comparison if needed, but here we round
        if ($expected != $actual) {
            $msg = $message ?: "Expected " . var_export($expected, true) . ", but got " . var_export($actual, true);
            throw new \Tests\AssertionFailedException($msg);
        }
    }
}
