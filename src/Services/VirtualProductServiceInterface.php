<?php
namespace App\Services;

use App\Models\Order;
use App\Models\CustomerDownload;

interface VirtualProductServiceInterface {
    public function fulfillDigitalItems(Order $order, array $items): void;
    public function generateDownloadToken(int $orderItemId, int $productId, ?int $variantId, ?int $userId): string;
    public function generateGiftCard(float $amount, string $recipientEmail, ?string $senderName, ?string $message, ?int $orderItemId): string;
    public function allocateLicenseKey(int $orderItemId, int $productId): string;
    public function upgradeUserRole(int $userId, string $role): void;
    public function generateEventTicket(int $orderItemId, ?int $userId): string;
    public function verifyDownloadToken(string $token): ?CustomerDownload;
    public function incrementDownloadCount(string $token): void;
    public function applyGiftCardCode(string $code, float $orderTotal): array;
    public function getActiveGiftCard(string $code): ?array;
    public function deductGiftCardBalance(string $code, float $amount): bool;
    
    // Digital library queries
    public function getUserDownloads(int $userId): array;
    public function getUserLicenses(int $userId): array;
    public function getUserTickets(int $userId): array;
}
