<?php
namespace App\Repositories;

use App\Models\Review;

interface ReviewRepositoryInterface {
    public function getByProductId(int $productId, bool $onlyApproved = true): array;
    public function submit(int $productId, int $userId, int $rating, ?string $comment): bool;
    public function getAllForAdmin(): array;
    public function updateStatus(int $reviewId, string $status): bool;
    public function getAverageRating(int $productId): float;
}
