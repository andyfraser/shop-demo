<?php

namespace App\Services;

use App\Models\Promotion;
use App\Repositories\PromotionRepositoryInterface;
use Psr\Log\LoggerInterface;

class PromotionService implements PromotionServiceInterface {
    public function __construct(
        private PromotionRepositoryInterface $repository,
        private LoggerInterface $logger,
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
            return $promotionId;
        } catch (\Exception $e) {
            $this->repository->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void {
        $this->repository->delete($id);
    }

    public function getActiveAutomaticPromotions(?\App\Models\User $user = null): array {
        return $this->getActivePromotions(true, $user);
    }

    public function getActivePromotions(bool $onlyAutomatic = false, ?\App\Models\User $user = null): array {
        $now = date('Y-m-d H:i:s');
        $promotions = $this->repository->getActivePromotions($now, $onlyAutomatic);

        foreach ($promotions as $promo) {
            $promo->target_ids = $this->repository->getTargetIds($promo->id);
            $promo->excluded_ids = $this->repository->getTargetIds($promo->id, true);
            $promo->tiers = $this->repository->getTiers($promo->id);
            $promo->additional_codes = $this->repository->getAdditionalCodes($promo->id);
            
            if ($user && isset($user->id)) {
                $promo->user_usage_count = $this->repository->getUserUsageCount($promo->id, $user->id);
            }
        }

        $isFirstOrder = $this->isFirstOrder($user);
        return array_values(array_filter($promotions, fn($p) => $p->isActive($user, $isFirstOrder)));
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
                if ($this->itemMatchesTarget($item, $promo)) {
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
        // Tiers can override the base value
        $value = $promotion->value;
        if (!empty($promotion->tiers)) {
            $sortedTiers = $promotion->tiers;
            usort($sortedTiers, fn($a, $b) => $b['min_amount'] <=> $a['min_amount']);
            foreach ($sortedTiers as $tier) {
                if ($subtotal >= $tier['min_amount']) {
                    $value = $tier['value'];
                    break;
                }
            }
        }

        if ($subtotal < $promotion->min_order_amount) {
            return 0.0;
        }

        $discount = 0.0;

        if ($promotion->target_type === Promotion::TARGET_ORDER) {
            if ($promotion->type === Promotion::TYPE_PERCENTAGE) {
                $discount = $subtotal * ($value / 100);
            } elseif ($promotion->type === Promotion::TYPE_FIXED_AMOUNT) {
                $discount = $value;
            } elseif ($promotion->type === Promotion::TYPE_BUY_X_GET_Y) {
                $qualifyingPrices = [];
                foreach ($cartItems as $item) {
                    for ($i = 0; $i < $item->qty; $i++) {
                        $qualifyingPrices[] = $item->unit_price;
                    }
                }
                $discount = $this->calculateBogoDiscount($promotion, $qualifyingPrices, $value);
            } elseif ($promotion->type === Promotion::TYPE_FREE_SHIPPING) {
                // Free shipping is usually handled in Checkout/Cart totals by zeroing delivery cost
                // but here we can return 0 and let the delivery service check for free shipping promo
                return 0.0;
            }
        } else {
            // Product or Category specific
            $qualifyingPrices = [];
            foreach ($cartItems as $item) {
                if ($this->itemMatchesTarget($item, $promotion)) {
                    if ($promotion->type === Promotion::TYPE_PERCENTAGE) {
                        $discount += ($item->unit_price * $item->qty) * ($value / 100);
                    } elseif ($promotion->type === Promotion::TYPE_FIXED_AMOUNT) {
                        $discount += $value * $item->qty;
                    } elseif ($promotion->type === Promotion::TYPE_BUY_X_GET_Y) {
                        for ($i = 0; $i < $item->qty; $i++) {
                            $qualifyingPrices[] = $item->unit_price;
                        }
                    }
                }
            }

            if ($promotion->type === Promotion::TYPE_BUY_X_GET_Y) {
                $discount = $this->calculateBogoDiscount($promotion, $qualifyingPrices, $value);
            }
        }

        // Cap discount at subtotal
        return min($discount, $subtotal);
    }

    private function calculateBogoDiscount(Promotion $promotion, array $prices, ?float $valueOverride = null): float {
        if (empty($prices)) {
            return 0.0;
        }

        $value = $valueOverride ?? $promotion->value;
        sort($prices); // Cheapest first
        $totalUnits = count($prices);
        $bundleSize = $promotion->buy_qty + $promotion->get_qty;
        
        if ($bundleSize <= 0) {
            return 0.0;
        }

        $discountUnits = floor($totalUnits / $bundleSize) * $promotion->get_qty;
        $discount = 0.0;

        for ($i = 0; $i < $discountUnits; $i++) {
            $discount += $prices[$i] * ($value / 100);
        }

        return $discount;
    }

    public function isProductQualifying(\App\Models\Product $product, Promotion $promotion): bool {
        // Exclusions take precedence
        if ($promotion->target_type === Promotion::TARGET_PRODUCT) {
            if (in_array($product->id, $promotion->excluded_ids)) return false;
            return empty($promotion->target_ids) || in_array($product->id, $promotion->target_ids);
        }
        
        if ($promotion->target_type === Promotion::TARGET_CATEGORY) {
            $categoryPath = [$product->category_id];
            if ($this->categoryService && $product->category_id) {
                $breadcrumb = $this->categoryService->getBreadcrumb($product->category_id);
                $categoryPath = array_map(fn($c) => (int)$c->id, $breadcrumb);
            }

            // Exclusions: if any category in the path is excluded, the product is excluded
            foreach ($categoryPath as $catId) {
                if (in_array($catId, $promotion->excluded_ids)) return false;
            }

            // Targets: if any category in the path is targeted, the product qualifies
            if (empty($promotion->target_ids)) return true;
            foreach ($categoryPath as $catId) {
                if (in_array($catId, $promotion->target_ids)) return true;
            }
            return false;
        }

        return true;
    }

    private function itemMatchesTarget(\App\Models\CartItem $item, Promotion $promotion): bool {
        return $this->isProductQualifying($item->product, $promotion);
    }
}
