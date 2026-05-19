<?php
namespace App\Services;

interface EmailServiceInterface {
    public function sendVerificationEmail(string $to, string $name, string $token): bool;
    public function sendOrderConfirmation(\App\Models\Order $order, array $items): bool;
    public function sendStatusUpdateEmail(string $to, \App\Models\Order $order, string $status): bool;
    public function sendReturnRequestedEmail(\App\Models\ReturnOrder $return, string $to): bool;
    public function sendReturnUpdateEmail(\App\Models\ReturnOrder $return, string $to): bool;
    public function sendAbandonedCartEmail(string $to, string $name): bool;
}
