<?php

namespace App\Services\Payment;

class PaymentResult {
    public function __construct(
        public bool $success,
        public string $status,
        public ?string $transactionId = null,
        public ?string $message = null
    ) {}

    public static function success(string $status = 'paid', ?string $transactionId = null): self {
        return new self(true, $status, $transactionId);
    }

    public static function failure(string $message, string $status = 'failed'): self {
        return new self(false, $status, null, $message);
    }
}
