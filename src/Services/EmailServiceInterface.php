<?php
namespace App\Services;

use App\Models\Order;

interface EmailServiceInterface {
    public function sendVerificationEmail(string $toEmail, string $name, string $token): bool;
    public function sendOrderConfirmation(Order $order, array $items): bool;
    public function sendStatusUpdateEmail(string $toEmail, int $orderId, string $status): bool;
}
