<?php
namespace App\Repositories;

use App\Models\Promotion;

interface PromotionRepositoryInterface {
    public function getAllForAdmin(): array;
    public function findById(int $id): ?Promotion;
    public function findByCode(string $code): ?Promotion;
    public function save(array $params, int $id = 0): int;
    public function delete(int $id): void;
    public function getActivePromotions(string $now, bool $onlyAutomatic = false): array;
    public function getTargetIds(int $promotionId, bool $isExclusion = false): array;
    public function syncTargets(int $promotionId, array $targetIds, bool $isExclusion = false): void;
    public function getTiers(int $promotionId): array;
    public function syncTiers(int $promotionId, array $tiers): void;
    public function getAdditionalCodes(int $promotionId): array;
    public function syncAdditionalCodes(int $promotionId, array $codes): void;
    public function getUserUsageCount(int $promotionId, int $userId): int;
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollBack(): void;
}
