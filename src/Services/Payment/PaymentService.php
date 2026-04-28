<?php

namespace App\Services\Payment;

use App\Models\Order;
use Psr\Log\LoggerInterface;

class PaymentService implements PaymentServiceInterface {
    /** @var PaymentGatewayInterface[] */
    private array $gateways = [];

    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function registerGateway(PaymentGatewayInterface $gateway): void {
        $this->gateways[$gateway->getIdentifier()] = $gateway;
    }

    public function getGateways(): array {
        return $this->gateways;
    }

    public function getGateway(string $identifier): ?PaymentGatewayInterface {
        return $this->gateways[$identifier] ?? null;
    }

    public function process(string $gatewayIdentifier, Order $order, array $options = []): PaymentResult {
        $gateway = $this->getGateway($gatewayIdentifier);
        
        if (!$gateway) {
            $this->logger->error("Payment gateway not found: {identifier}", ['identifier' => $gatewayIdentifier]);
            return PaymentResult::failure("Payment gateway '{$gatewayIdentifier}' not found.");
        }

        $this->logger->info("Processing payment via {gateway} for order {order_id}", [
            'gateway' => $gatewayIdentifier,
            'order_id' => $order->id,
            'amount' => $order->total
        ]);

        try {
            $result = $gateway->process($order, $options);
            
            if ($result->success) {
                $this->logger->info("Payment successful for order {order_id}. Transaction ID: {transaction_id}", [
                    'order_id' => $order->id,
                    'transaction_id' => $result->transactionId
                ]);
            } else {
                $this->logger->warning("Payment failed for order {order_id}. Reason: {message}", [
                    'order_id' => $order->id,
                    'message' => $result->message
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Payment processing error: " . $e->getMessage(), [
                'gateway' => $gatewayIdentifier,
                'order_id' => $order->id
            ]);
            return PaymentResult::failure("An error occurred during payment processing.");
        }
    }

    public function refund(string $gatewayIdentifier, Order $order, ?float $amount = null): PaymentResult {
        $gateway = $this->getGateway($gatewayIdentifier);
        
        if (!$gateway) {
            $this->logger->error("Payment gateway not found for refund: {identifier}", ['identifier' => $gatewayIdentifier]);
            return PaymentResult::failure("Payment gateway '{$gatewayIdentifier}' not found.");
        }

        $refundAmount = $amount ?? $order->total;
        $this->logger->info("Processing refund via {gateway} for order {order_id}", [
            'gateway' => $gatewayIdentifier,
            'order_id' => $order->id,
            'amount' => $refundAmount
        ]);

        try {
            $result = $gateway->refund($order, $amount);
            
            if ($result->success) {
                $this->logger->info("Refund successful for order {order_id}. Amount: {amount}. Transaction ID: {transaction_id}", [
                    'order_id' => $order->id,
                    'amount' => $refundAmount,
                    'transaction_id' => $result->transactionId
                ]);
            } else {
                $this->logger->warning("Refund failed for order {order_id}. Reason: {message}", [
                    'order_id' => $order->id,
                    'message' => $result->message
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Refund processing error: " . $e->getMessage(), [
                'gateway' => $gatewayIdentifier,
                'order_id' => $order->id
            ]);
            return PaymentResult::failure("An error occurred during refund processing.");
        }
    }
}
