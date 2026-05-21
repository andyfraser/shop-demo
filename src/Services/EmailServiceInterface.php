<?php
namespace App\Services;

interface EmailServiceInterface {
    public function sendVerificationEmail(string $to, string $name, string $token): bool;
    public function sendOrderConfirmation(\App\Models\Order $order, array $items): bool;
    public function sendStatusUpdateEmail(string $to, \App\Models\Order $order, string $status): bool;
    public function sendReturnRequestedEmail(\App\Models\ReturnOrder $return, string $to): bool;
    public function sendReturnUpdateEmail(\App\Models\ReturnOrder $return, string $to): bool;
    public function sendAbandonedCartEmail(string $to, string $name): bool;
    public function sendDigitalDownloadsEmail(string $toEmail, string $customerName, array $downloads): bool;
    public function sendGiftCardEmail(string $toEmail, string $recipientName, string $senderName, string $code, float $amount, ?string $message): bool;
    public function sendLicenseKeyEmail(string $toEmail, string $customerName, array $licenses): bool;
    public function sendEventTicketEmail(string $toEmail, string $customerName, array $tickets): bool;
}
