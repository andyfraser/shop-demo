<?php
namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\GiftCard;
use App\Models\CustomerDownload;
use App\Models\CustomerTicket;
use App\Repositories\ProductRepositoryInterface;
use App\Repositories\VirtualProductRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use Psr\Log\LoggerInterface;

class VirtualProductService implements VirtualProductServiceInterface {
    public function __construct(
        private VirtualProductRepositoryInterface $virtualProductRepository,
        private UserRepositoryInterface $userRepository,
        private ProductRepositoryInterface $productRepository,
        private EmailServiceInterface $emailService,
        private LoggerInterface $logger
    ) {}

    public function fulfillDigitalItems(Order $order, array $items): void {
        $this->logger->info("Fulfilling digital items for order {orderId} (if any)", ['orderId' => $order->id]);

        $downloads = [];
        $giftCards = [];
        $licenses = [];
        $tickets = [];

        foreach ($items as $item) {
            $product = $this->productRepository->findById($item->product_id);
            if (!$product) continue;

            if ($product->is_bundle) {
                // Resolve bundle items
                $bundleComponents = $this->productRepository->getBundleItems($product->id);
                foreach ($bundleComponents as $component) {
                    $compProduct = $this->productRepository->findById($component['product_id']);
                    if (!$compProduct || !$compProduct->is_virtual) continue;

                    // component quantity is item quantity * bundle quantity
                    $qty = $item->quantity * $component['bundle_qty'];
                    $this->fulfillItem(
                        $order,
                        $item->id,
                        $compProduct,
                        null, // Variant is null for bundle components for simplicity
                        $qty,
                        $compProduct->price, // fallback amount for gift cards
                        $item->metadata,
                        $downloads,
                        $giftCards,
                        $licenses,
                        $tickets
                    );
                }
            } elseif ($product->is_virtual) {
                $variant = $item->variant_id ? $this->productRepository->findVariantById($item->variant_id) : null;
                $this->fulfillItem(
                    $order,
                    $item->id,
                    $product,
                    $variant,
                    $item->quantity,
                    $item->unit_price,
                    $item->metadata,
                    $downloads,
                    $giftCards,
                    $licenses,
                    $tickets
                );
            }
        }

        // Send consolidated emails
        if (!empty($downloads)) {
            $this->emailService->sendDigitalDownloadsEmail($order->customer_email, $order->customer_name, $downloads);
        }

        if (!empty($licenses)) {
            $this->emailService->sendLicenseKeyEmail($order->customer_email, $order->customer_name, $licenses);
        }

        if (!empty($tickets)) {
            $this->emailService->sendEventTicketEmail($order->customer_email, $order->customer_name, $tickets);
        }

        // Send individual gift card emails
        foreach ($giftCards as $gc) {
            $this->emailService->sendGiftCardEmail(
                $gc['recipient_email'],
                $gc['recipient_email'], // Recipient name fallback to email
                $gc['sender_name'] ?: 'A Friend',
                $gc['code'],
                $gc['amount'],
                $gc['message']
            );
        }
    }

    private function fulfillItem(
        Order $order,
        int $orderItemId,
        $product,
        $variant,
        int $quantity,
        float $unitPrice,
        ?string $metadataJson,
        array &$downloads,
        array &$giftCards,
        array &$licenses,
        array &$tickets
    ): void {
        $type = $product->virtual_type;
        $this->logger->info("Fulfilling digital item of type {type} for product {prodName}", [
            'type' => $type,
            'prodName' => $product->name
        ]);

        $metadata = [];
        if ($metadataJson) {
            $metadata = json_decode($metadataJson, true) ?: [];
        }

        if ($type === 'file') {
            for ($i = 0; $i < $quantity; $i++) {
                $token = $this->generateDownloadToken($orderItemId, $product->id, $variant?->id, $order->user_id);
                $downloads[] = [
                    'name' => $product->name . ($variant ? " ({$variant->name})" : ""),
                    'token' => $token
                ];
            }
        } elseif ($type === 'giftcard') {
            // Recipient details from metadata
            $recipientEmail = $metadata['recipient_email'] ?? $order->customer_email;
            $senderName = $metadata['sender_name'] ?? $order->customer_name;
            $message = $metadata['message'] ?? '';
            
            // Total amount for this item
            $amount = $unitPrice * $quantity;
            $code = $this->generateGiftCard($amount, $recipientEmail, $senderName, $message, $orderItemId);
            
            $giftCards[] = [
                'code' => $code,
                'amount' => $amount,
                'recipient_email' => $recipientEmail,
                'sender_name' => $senderName,
                'message' => $message
            ];
        } elseif ($type === 'license') {
            for ($i = 0; $i < $quantity; $i++) {
                $key = $this->allocateLicenseKey($orderItemId, $product->id);
                $licenses[] = [
                    'name' => $product->name,
                    'key' => $key
                ];
            }
        } elseif ($type === 'membership') {
            if ($order->user_id) {
                // If variant specifies a role, use it; otherwise use product granted_role
                $roleToGrant = $variant?->granted_role ?: $product->granted_role;
                if ($roleToGrant) {
                    $this->upgradeUserRole($order->user_id, $roleToGrant);
                }
            }
        } elseif ($type === 'event_ticket') {
            for ($i = 0; $i < $quantity; $i++) {
                $code = $this->generateEventTicket($orderItemId, $order->user_id);
                $tickets[] = [
                    'name' => $product->name,
                    'code' => $code
                ];
            }
        }
    }

    public function generateDownloadToken(int $orderItemId, int $productId, ?int $variantId, ?int $userId): string {
        $token = bin2hex(random_bytes(16));
        $this->virtualProductRepository->createDownloadToken($orderItemId, $productId, $variantId, $token, $userId);
        return $token;
    }

    public function generateGiftCard(float $amount, string $recipientEmail, ?string $senderName, ?string $message, ?int $orderItemId): string {
        $code = "GIFT-" . strtoupper(bin2hex(random_bytes(4))) . "-" . strtoupper(bin2hex(random_bytes(4)));
        $this->virtualProductRepository->createGiftCard($code, $amount, $recipientEmail, $senderName, $message, $orderItemId);
        return $code;
    }

    public function allocateLicenseKey(int $orderItemId, int $productId): string {
        // Try to allocate from pool
        $row = $this->virtualProductRepository->findUnassignedLicenseKey($productId);

        if ($row) {
            $keyId = (int)$row['id'];
            $key = $row['license_key'];
            $this->virtualProductRepository->markLicenseKeyAsAssigned($keyId);
        } else {
            // Fallback key generation
            $key = "LIC-" . strtoupper(bin2hex(random_bytes(4))) . "-" . strtoupper(bin2hex(random_bytes(4))) . "-" . strtoupper(bin2hex(random_bytes(4)));
        }

        $this->virtualProductRepository->recordOrderItemLicense($orderItemId, $key);

        return $key;
    }

    public function upgradeUserRole(int $userId, string $role): void {
        $user = $this->userRepository->findById($userId);
        if ($user && $user->role === 'admin') {
            $this->logger->info("Skipping role upgrade for admin user {userId} because they are already an admin", ['userId' => $userId]);
            return;
        }

        $this->logger->info("Upgraded user {userId} to role {role}", ['userId' => $userId, 'role' => $role]);
        $this->userRepository->updateRole($userId, $role);
    }

    public function generateEventTicket(int $orderItemId, ?int $userId): string {
        $code = "TKT-" . strtoupper(bin2hex(random_bytes(4))) . "-" . strtoupper(bin2hex(random_bytes(4)));
        $this->virtualProductRepository->createEventTicket($orderItemId, $userId, $code);
        return $code;
    }

    public function verifyDownloadToken(string $token): ?CustomerDownload {
        return $this->virtualProductRepository->findDownloadByToken($token);
    }

    public function incrementDownloadCount(string $token): void {
        $this->virtualProductRepository->incrementDownloadCount($token);
    }

    public function applyGiftCardCode(string $code, float $orderTotal): array {
        $gc = $this->getActiveGiftCard($code);
        if (!$gc) {
            return ['success' => false, 'message' => 'Invalid or inactive gift card.'];
        }

        $remaining = (float)$gc['remaining_amount'];
        if ($remaining <= 0) {
            return ['success' => false, 'message' => 'This gift card has a balance of 0.00.'];
        }

        $discount = min($remaining, $orderTotal);
        return [
            'success' => true,
            'discount' => $discount,
            'remaining' => $remaining - $discount
        ];
    }

    public function getActiveGiftCard(string $code): ?array {
        return $this->virtualProductRepository->findActiveGiftCard($code);
    }

    public function deductGiftCardBalance(string $code, float $amount): bool {
        $gc = $this->getActiveGiftCard($code);
        if (!$gc) return false;

        $remaining = (float)$gc['remaining_amount'];
        $newRemaining = max(0.0, $remaining - $amount);

        return $this->virtualProductRepository->updateGiftCardBalance($code, $newRemaining);
    }

    public function getUserDownloads(int $userId): array {
        return $this->virtualProductRepository->getDownloadsByUserId($userId);
    }

    public function getUserLicenses(int $userId): array {
        return $this->virtualProductRepository->getLicensesByUserId($userId);
    }

    public function getUserTickets(int $userId): array {
        return $this->virtualProductRepository->getTicketsByUserId($userId);
    }
}
