<?php
namespace App\Repositories;

use App\Models\CustomerDownload;
use Psr\Log\LoggerInterface;

class VirtualProductRepository implements VirtualProductRepositoryInterface {
    public function __construct(
        private \PDO $db,
        private LoggerInterface $logger
    ) {}

    public function createDownloadToken(int $orderItemId, int $productId, ?int $variantId, string $token, ?int $userId): void {
        $stmt = $this->db->prepare(
            "INSERT INTO customer_downloads (order_item_id, product_id, variant_id, download_token, download_count, user_id)
             VALUES (?, ?, ?, ?, 0, ?)"
        );
        $stmt->execute([$orderItemId, $productId, $variantId, $token, $userId]);
    }

    public function createGiftCard(string $code, float $amount, string $recipientEmail, ?string $senderName, ?string $message, ?int $orderItemId): void {
        $stmt = $this->db->prepare(
            "INSERT INTO gift_cards (code, initial_amount, remaining_amount, recipient_email, sender_name, message, order_item_id, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
        );
        $stmt->execute([$code, $amount, $amount, $recipientEmail, $senderName, $message, $orderItemId]);
    }

    public function findUnassignedLicenseKey(int $productId): ?array {
        $stmt = $this->db->prepare("SELECT id, license_key FROM product_license_keys WHERE product_id = ? AND is_assigned = 0 LIMIT 1");
        $stmt->execute([$productId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function markLicenseKeyAsAssigned(int $keyId): void {
        $update = $this->db->prepare("UPDATE product_license_keys SET is_assigned = 1 WHERE id = ?");
        $update->execute([$keyId]);
    }

    public function recordOrderItemLicense(int $orderItemId, string $key): void {
        $insert = $this->db->prepare("INSERT INTO order_item_licenses (order_item_id, license_key) VALUES (?, ?)");
        $insert->execute([$orderItemId, $key]);
    }

    public function createEventTicket(int $orderItemId, ?int $userId, string $code): void {
        $stmt = $this->db->prepare("INSERT INTO customer_tickets (user_id, order_item_id, ticket_code) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $orderItemId, $code]);
    }

    public function findDownloadByToken(string $token): ?CustomerDownload {
        $stmt = $this->db->prepare(
            "SELECT cd.*, o.customer_email, o.user_id as order_user_id 
             FROM customer_downloads cd 
             JOIN order_items oi ON cd.order_item_id = oi.id 
             JOIN orders o ON oi.order_id = o.id 
             WHERE cd.download_token = ?"
        );
        $stmt->setFetchMode(\PDO::FETCH_CLASS, CustomerDownload::class, [$this->logger]);
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public function incrementDownloadCount(string $token): void {
        $stmt = $this->db->prepare("UPDATE customer_downloads SET download_count = download_count + 1 WHERE download_token = ?");
        $stmt->execute([$token]);
    }

    public function findActiveGiftCard(string $code): ?array {
        $stmt = $this->db->prepare("SELECT * FROM gift_cards WHERE code = ? AND is_active = 1");
        $stmt->execute([$code]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function updateGiftCardBalance(string $code, float $newBalance): bool {
        $stmt = $this->db->prepare("UPDATE gift_cards SET remaining_amount = ? WHERE code = ?");
        return $stmt->execute([$newBalance, $code]);
    }

    public function getDownloadsByUserId(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT cd.*, p.name as product_name, pv.name as variant_name 
             FROM customer_downloads cd 
             JOIN products p ON cd.product_id = p.id 
             LEFT JOIN product_variants pv ON cd.variant_id = pv.id 
             WHERE cd.user_id = ? OR cd.order_item_id IN (
                 SELECT oi.id FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = ?
             )
             ORDER BY cd.created_at DESC"
        );
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getLicensesByUserId(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT oil.*, p.name as product_name 
             FROM order_item_licenses oil 
             JOIN order_items oi ON oil.order_item_id = oi.id 
             JOIN orders o ON oi.order_id = o.id 
             JOIN products p ON oi.product_id = p.id 
             WHERE o.user_id = ?
             ORDER BY oil.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTicketsByUserId(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT ct.*, p.name as product_name 
             FROM customer_tickets ct 
             JOIN order_items oi ON ct.order_item_id = oi.id 
             JOIN products p ON oi.product_id = p.id 
             WHERE ct.user_id = ? OR ct.order_item_id IN (
                 SELECT oi.id FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = ?
             )
             ORDER BY ct.created_at DESC"
        );
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
