<?php

namespace App\Services\Payment;

use App\Models\Order;

class MockCardGateway implements PaymentGatewayInterface {
    public function getIdentifier(): string {
        return 'mock_card';
    }

    public function getName(): string {
        return 'Credit / Debit Card (Mock)';
    }

    public function process(Order $order, array $options = []): PaymentResult {
        // Simulate small random latency (400ms to 1200ms)
        usleep(random_int(400000, 1200000));

        $cvc = trim($options['card_cvc'] ?? '');

        // CVC-based error simulation
        switch ($cvc) {
            case '999':
                return PaymentResult::failure('Card declined: Insufficient funds.');
            case '998':
                throw new \RuntimeException('Connection to payment gateway timed out.');
            case '997':
                return PaymentResult::failure('Card declined: Card has expired.');
            case '996':
                return PaymentResult::failure('Card declined: Suspected of fraud.');
            default:
                return PaymentResult::success('paid', 'MOCK-ST-SUCCESS-' . uniqid());
        }
    }

    public function refund(Order $order, ?float $amount = null): PaymentResult {
        // Simulate latency for refunds as well
        usleep(random_int(200000, 500000));
        return PaymentResult::success('refunded', 'MOCK-ST-REFUND-' . uniqid());
    }
}
