<?php
namespace App\Repositories;

use App\Models\CustomerDownload;

interface VirtualProductRepositoryInterface {
    public function createDownloadToken(int $orderItemId, int $productId, ?int $variantId, string $token, ?int $userId): void;
    
    public function createGiftCard(string $code, float $amount, string $recipientEmail, ?string $senderName, ?string $message, ?int $orderItemId): void;
    
    public function findUnassignedLicenseKey(int $productId): ?array;
    
    public function markLicenseKeyAsAssigned(int $keyId): void;
    
    public function recordOrderItemLicense(int $orderItemId, string $key): void;
    
    public function createEventTicket(int $orderItemId, ?int $userId, string $code): void;
    
    public function findDownloadByToken(string $token): ?CustomerDownload;
    
    public function incrementDownloadCount(string $token): void;
    
    public function findActiveGiftCard(string $code): ?array;
    
    public function updateGiftCardBalance(string $code, float $newBalance): bool;
    
    public function getDownloadsByUserId(int $userId): array;
    
    public function getLicensesByUserId(int $userId): array;
    
    public function getTicketsByUserId(int $userId): array;
}
