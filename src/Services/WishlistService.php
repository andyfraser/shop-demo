<?php

namespace App\Services;

use App\Repositories\WishlistRepositoryInterface;
use Psr\Log\LoggerInterface;

class WishlistService implements WishlistServiceInterface {
    public function __construct(
        private WishlistRepositoryInterface $repository,
        private ProductServiceInterface $productService,
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
}
