<?php

namespace App\Services;

use App\Repositories\WishlistRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use Psr\Log\LoggerInterface;

class WishlistService implements WishlistServiceInterface {
    public function __construct(
        private WishlistRepositoryInterface $repository,
        private ProductServiceInterface $productService,
        private UserRepositoryInterface $userRepository,
        private LoggerInterface $logger
    ) {}

    public function getUserWishlist(int $userId): array {
        $productIds = $this->repository->getUserProductIds($userId);

        if (empty($productIds)) {
            return [];
        }

        return $this->productService->findByIds($productIds);
    }

    public function addToWishlist(int $userId, int $productId): bool {
        return $this->repository->add($userId, $productId);
    }

    public function removeFromWishlist(int $userId, int $productId): bool {
        return $this->repository->remove($userId, $productId);
    }

    public function isInWishlist(int $userId, int $productId): bool {
        return $this->repository->exists($userId, $productId);
    }

    public function getSettings(int $userId): array {
        $settings = $this->repository->getSettings($userId);
        if (!$settings) {
            return [
                'user_id' => $userId,
                'is_public' => 0,
                'share_hash' => null
            ];
        }
        return $settings;
    }

    public function togglePrivacy(int $userId, bool $isPublic): array {
        $settings = $this->getSettings($userId);
        $shareHash = $settings['share_hash'];

        if ($isPublic && !$shareHash) {
            $shareHash = bin2hex(random_bytes(16));
        }

        $this->repository->updateSettings($userId, $isPublic, $shareHash);
        
        return [
            'user_id' => $userId,
            'is_public' => (int)$isPublic,
            'share_hash' => $shareHash
        ];
    }

    public function getSharedWishlist(string $shareHash): ?array {
        $userId = $this->repository->getUserIdByShareHash($shareHash);
        if (!$userId) {
            return null;
        }

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return null;
        }

        return [
            'user' => $user,
            'products' => $this->getUserWishlist($userId)
        ];
    }
}
