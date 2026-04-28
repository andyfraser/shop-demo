<?php

namespace App\Services\Payment;

use App\Models\Order;

interface PaymentGatewayInterface {
    /**
     * Get the unique identifier for this gateway (e.g., 'manual', 'stripe').
     */
    public function getIdentifier(): string;

    /**
     * Get the display name for this gateway.
     */
    public function getName(): string;

    /**
     * Process payment for an order.
     */
    public function process(Order $order, array $options = []): PaymentResult;

    /**
     * Refund a payment for an order.
     */
    public function refund(Order $order, ?float $amount = null): PaymentResult;
}
