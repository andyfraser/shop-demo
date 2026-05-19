<?php
namespace App\Services;

use App\Repositories\ReviewRepositoryInterface;
use Psr\Log\LoggerInterface;

class ReviewService implements ReviewServiceInterface {
    public function __construct(
        private ReviewRepositoryInterface $repository,
        private LoggerInterface $logger,
        private \App\Core\Events\EventDispatcherInterface $eventDispatcher
    ) {}

    public function getByProductId(int $productId, bool $onlyApproved = true): array {
        return $this->repository->getByProductId($productId, $onlyApproved);
    }

    public function submit(int $productId, int $userId, int $rating, ?string $comment): bool {
        $result = $this->repository->submit($productId, $userId, $rating, $comment);
        if ($result) {
            $this->eventDispatcher->dispatch(new \App\Events\ReviewSubmitted([
                'productId' => $productId,
                'userId' => $userId,
                'rating' => $rating,
                'comment' => $comment
            ]));
        }
        return $result;
    }

    public function getAllForAdmin(): array {
        return $this->repository->getAllForAdmin();
    }

    public function updateStatus(int $reviewId, string $status): bool {
        $result = $this->repository->updateStatus($reviewId, $status);
        if ($result) {
            if ($status === 'approved') {
                $this->eventDispatcher->dispatch(new \App\Events\ReviewApproved($reviewId));
            } else {
                $this->logger->info("Review {id} status updated to {status}", [
                    'id' => $reviewId,
                    'status' => $status
                ]);
            }
        }
        return $result;
    }

    public function getAverageRating(int $productId): float {
        return $this->repository->getAverageRating($productId);
    }
}
