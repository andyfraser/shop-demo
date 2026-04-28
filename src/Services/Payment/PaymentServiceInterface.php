<?php

namespace App\Services\Payment;

use App\Models\Order;

interface PaymentServiceInterface {
    /**
     * Add a gateway to the service.
     */
    public function registerGateway(PaymentGatewayInterface $gateway): void;

    /**
     * Get all registered gateways.
     * @return PaymentGatewayInterface[]
     */
    public function getGateways(): array;

    /**
     * Get a gateway by identifier.
     */
    public function getGateway(string $identifier): ?PaymentGatewayInterface;

    /**
     * Process payment using the specified gateway.
     */
    public function process(string $gatewayIdentifier, Order $order, array $options = []): PaymentResult;

    /**
     * Refund a payment using the specified gateway.
     */
    public function refund(string $gatewayIdentifier, Order $order, ?float $amount = null): PaymentResult;
}
