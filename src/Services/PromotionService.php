<?php

namespace App\Services;

use App\Models\Promotion;
use App\Repositories\PromotionRepositoryInterface;
use App\Services\PromotionEvaluatorInterface;
use Psr\Log\LoggerInterface;
use App\Core\Cache\CacheInterface;

class PromotionService implements PromotionServiceInterface {
    public function __construct(
        private PromotionRepositoryInterface $repository,
        private PromotionEvaluatorInterface $evaluator,
        private LoggerInterface $logger,
        private CacheInterface $cache,
        private ?CategoryServiceInterface $categoryService = null,
        private ?OrderServiceInterface $orderService = null
    ) {}

    private function isFirstOrder(?\App\Models\User $user): bool {
        if (!$user) return true;
        if (!$this->orderService) return false;
        return !$this->orderService->hasOrders($user->id);
    }

    public function getAllForAdmin(): array {
        return $this->repository->getAllForAdmin();
    }

    public function findById(int $id): ?Promotion {
        $promotion = $this->repository->findById($id);

        if ($promotion) {
            $promotion->target_ids = $this->repository->getTargetIds($id);
            $promotion->excluded_ids = $this->repository->getTargetIds($id, true);
            $promotion->tiers = $this->repository->getTiers($id);
            $promotion->additional_codes = $this->repository->getAdditionalCodes($id);
        }

        return $promotion;
    }

    public function findByCode(string $code): ?Promotion {
        $promotion = $this->repository->findByCode($code);

        if ($promotion) {
            $promotion->target_ids = $this->repository->getTargetIds($promotion->id);
            $promotion->excluded_ids = $this->repository->getTargetIds($promotion->id, true);
            $promotion->tiers = $this->repository->getTiers($promotion->id);
            $promotion->additional_codes = $this->repository->getAdditionalCodes($promotion->id);
        }

        return $promotion;
    }

    public function save(array|Promotion $data, int $id = 0): int {
        $isObject = $data instanceof Promotion;
        $normalizeDate = fn($d) => !empty($d) ? str_replace('T', ' ', $d) : null;

        $params = $isObject ? [
            'name' => $data->name,
            'description' => $data->description,
            'code' => $data->code ?: null,
            'type' => $data->type,
            'value' => $data->value,
            'buy_qty' => $data->buy_qty,
            'get_qty' => $data->get_qty,
            'target_type' => $data->target_type,
            'min_order_amount' => $data->min_order_amount,
            'start_date' => $normalizeDate($data->start_date),
            'end_date' => $normalizeDate($data->end_date),
            'usage_limit' => $data->usage_limit,
            'usage_limit_per_user' => $data->usage_limit_per_user,
            'priority' => $data->priority,
            'stackable' => (int)$data->stackable,
            'target_role' => $data->target_role,
            'active' => (int)$data->active
        ] : [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'code' => !empty($data['code']) ? $data['code'] : null,
            'type' => $data['type'],
            'value' => (float)$data['value'],
            'buy_qty' => !empty($data['buy_qty']) ? (int)$data['buy_qty'] : null,
            'get_qty' => !empty($data['get_qty']) ? (int)$data['get_qty'] : null,
            'target_type' => $data['target_type'],
            'min_order_amount' => (float)($data['min_order_amount'] ?? 0),
            'start_date' => $normalizeDate($data['start_date'] ?? null),
            'end_date' => $normalizeDate($data['end_date'] ?? null),
            'usage_limit' => isset($data['usage_limit']) && $data['usage_limit'] !== '' ? (int)$data['usage_limit'] : null,
            'usage_limit_per_user' => isset($data['usage_limit_per_user']) && $data['usage_limit_per_user'] !== '' ? (int)$data['usage_limit_per_user'] : null,
            'priority' => (int)($data['priority'] ?? 0),
            'stackable' => isset($data['stackable']) ? (int)$data['stackable'] : 0,
            'target_role' => !empty($data['target_role']) ? $data['target_role'] : null,
            'active' => isset($data['active']) ? (int)$data['active'] : 1
        ];

        try {
            $this->repository->beginTransaction();

            $promotionId = $this->repository->save($params, $id);

            // Sync targets
            $targetIds = $isObject ? $data->target_ids : ($data['target_ids'] ?? []);
            $excludedIds = $isObject ? $data->excluded_ids : ($data['excluded_ids'] ?? []);
            if ($params['target_type'] === Promotion::TARGET_ORDER) {
                $targetIds = [];
                $excludedIds = [];
            }
            $this->repository->syncTargets($promotionId, $targetIds, false);
            $this->repository->syncTargets($promotionId, $excludedIds, true);

            // Sync tiers
            $tiers = $isObject ? $data->tiers : ($data['tiers'] ?? []);
            $this->repository->syncTiers($promotionId, $tiers);

            // Sync additional codes
            $additionalCodes = $isObject ? $data->additional_codes : ($data['additional_codes'] ?? []);
            $this->repository->syncAdditionalCodes($promotionId, $additionalCodes);

            $this->repository->commit();

            $this->clearCache();

            if ($this->logger) {
                $action = $id > 0 ? 'updated' : 'created';
                $this->logger->info("Promotion {name} (ID: {id}) was {action}.", [
                    'name' => $params['name'],
                    'id' => $promotionId,
                    'action' => $action
                ]);
            }

            return $promotionId;
        } catch (\Exception $e) {
            $this->repository->rollBack();
            if ($this->logger) {
                $this->logger->error("Failed to save promotion: {error}", ['error' => $e->getMessage()]);
            }
            throw $e;
        }
    }

    public function delete(int $id): void {
        $promotion = $this->findById($id);
        $this->repository->delete($id);
        $this->clearCache();
        if ($this->logger && $promotion) {
            $this->logger->info("Promotion {name} (ID: {id}) was deleted.", [
                'name' => $promotion->name,
                'id' => $id
            ]);
        }
    }

    public function getActiveAutomaticPromotions(?\App\Models\User $user = null): array {
        return $this->getActivePromotions(true, $user);
    }

    public function getActivePromotions(bool $onlyAutomatic = false, ?\App\Models\User $user = null): array {
        $cacheKey = 'active_promotions_' . ($onlyAutomatic ? 'auto' : 'all');
        $promotions = $this->cache->get($cacheKey);

        if ($promotions === null) {
            $now = date('Y-m-d H:i:s');
            $promotions = $this->repository->getActivePromotions($now, $onlyAutomatic);

            foreach ($promotions as $promo) {
                $promo->target_ids = $this->repository->getTargetIds($promo->id);
                $promo->excluded_ids = $this->repository->getTargetIds($promo->id, true);
                $promo->tiers = $this->repository->getTiers($promo->id);
                $promo->additional_codes = $this->repository->getAdditionalCodes($promo->id);
            }
            $this->cache->set($cacheKey, $promotions, 600); // Cache for 10 minutes
        }

        foreach ($promotions as $promo) {
            if ($user && isset($user->id)) {
                $promo->user_usage_count = $this->repository->getUserUsageCount($promo->id, $user->id);
            }
        }

        $isFirstOrder = $this->isFirstOrder($user);
        return array_values(array_filter($promotions, fn($p) => $p->isActive($user, $isFirstOrder)));
    }

    public function clearCache(): void {
        $this->cache->delete('active_promotions_auto');
        $this->cache->delete('active_promotions_all');
    }

    public function validateCode(string $code, array $cartItems, float $subtotal, ?\App\Models\User $user = null): ?Promotion {
        $promo = $this->findByCode($code);
        
        if ($promo && $user && isset($user->id)) {
            $promo->user_usage_count = $this->repository->getUserUsageCount($promo->id, $user->id);
        }

        if (!$promo || !$promo->isActive($user, $this->isFirstOrder($user))) {
            return null;
        }

        if ($subtotal < $promo->min_order_amount) {
            return null;
        }

        // Check if any items match targets if target_type is not 'order'
        if ($promo->target_type !== Promotion::TARGET_ORDER) {
            $hasTarget = false;
            foreach ($cartItems as $item) {
                if ($this->evaluator->isProductQualifying($item->product, $promo)) {
                    $hasTarget = true;
                    break;
                }
            }
            if (!$hasTarget) {
                return null;
            }
        }

        return $promo;
    }

    public function calculateDiscount(Promotion $promotion, array $cartItems, float $subtotal): float {
        return $this->evaluator->calculateDiscount($promotion, $cartItems, $subtotal);
    }

    public function isProductQualifying(\App\Models\Product $product, Promotion $promotion): bool {
        return $this->evaluator->isProductQualifying($product, $promotion);
    }
}
