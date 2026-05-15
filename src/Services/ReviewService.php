<?php
namespace App\Services;

use App\Repositories\ReviewRepositoryInterface;
use Psr\Log\LoggerInterface;

class ReviewService implements ReviewServiceInterface {
    public function __construct(
        private ReviewRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}

    public function getByProductId(int $productId, bool $onlyApproved = true): array {
        return $this->repository->getByProductId($productId, $onlyApproved);
    }

    public function submit(int $productId, int $userId, int $rating, ?string $comment): bool {
        return $this->repository->submit($productId, $userId, $rating, $comment);
    }

    public function getAllForAdmin(): array {
        return $this->repository->getAllForAdmin();
    }

    public function updateStatus(int $reviewId, string $status): bool {
        return $this->repository->updateStatus($reviewId, $status);
    }

    public function getAverageRating(int $productId): float {
        return $this->repository->getAverageRating($productId);
    }
}
