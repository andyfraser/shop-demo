<?php

namespace App\Services\Payment;

use App\Models\Order;

class ManualGateway implements PaymentGatewayInterface {
    public function getIdentifier(): string {
        return 'manual';
    }

    public function getName(): string {
        return 'Manual / Test Payment';
    }

    public function process(Order $order, array $options = []): PaymentResult {
        // For educational/test purposes, this always succeeds instantly.
        return PaymentResult::success('paid', 'TEST-' . uniqid());
    }

    public function refund(Order $order, ?float $amount = null): PaymentResult {
        // For educational/test purposes, this always succeeds instantly.
        return PaymentResult::success('refunded', 'REFUND-' . uniqid());
    }
}
