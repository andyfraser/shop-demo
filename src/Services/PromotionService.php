<?php

namespace App\Services;

use App\Models\Promotion;
use Psr\Log\LoggerInterface;
use PDO;

class PromotionService implements PromotionServiceInterface {
    public function __construct(
        private PDO $db,
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
        $stmt = $this->db->query("SELECT * FROM promotions ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_CLASS, Promotion::class, [$this->logger]);
    }

    public function findById(int $id): ?Promotion {
        $stmt = $this->db->prepare("SELECT * FROM promotions WHERE id = ?");
        $stmt->setFetchMode(PDO::FETCH_CLASS, Promotion::class, [$this->logger]);
        $stmt->execute([$id]);
        $promotion = $stmt->fetch() ?: null;

        if ($promotion) {
            $promotion->target_ids = $this->getTargetIds($id);
            $promotion->excluded_ids = $this->getTargetIds($id, true);
            $promotion->tiers = $this->getTiers($id);
            $promotion->additional_codes = $this->getAdditionalCodes($id);
        }

        return $promotion;
    }

    public function findByCode(string $code): ?Promotion {
        $stmt = $this->db->prepare("SELECT p.* FROM promotions p 
                                    LEFT JOIN promotion_codes pc ON p.id = pc.promotion_id 
                                    WHERE p.code = ? OR pc.code = ?");
        $stmt->setFetchMode(PDO::FETCH_CLASS, Promotion::class, [$this->logger]);
        $stmt->execute([$code, $code]);
        $promotion = $stmt->fetch() ?: null;

        if ($promotion) {
            $promotion->target_ids = $this->getTargetIds($promotion->id);
            $promotion->excluded_ids = $this->getTargetIds($promotion->id, true);
            $promotion->tiers = $this->getTiers($promotion->id);
            $promotion->additional_codes = $this->getAdditionalCodes($promotion->id);
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
            $this->db->beginTransaction();

            if ($id > 0) {
                $sql = "UPDATE promotions SET name = :name, description = :description, code = :code, type = :type, 
                        value = :value, buy_qty = :buy_qty, get_qty = :get_qty, target_type = :target_type, 
                        min_order_amount = :min_order_amount, start_date = :start_date, end_date = :end_date, 
                        usage_limit = :usage_limit, usage_limit_per_user = :usage_limit_per_user, 
                        priority = :priority, stackable = :stackable, target_role = :target_role, active = :active 
                        WHERE id = :id";
                $params['id'] = $id;
                $this->db->prepare($sql)->execute($params);
                $promotionId = $id;
            } else {
                $sql = "INSERT INTO promotions (name, description, code, type, value, buy_qty, get_qty, target_type, min_order_amount, start_date, end_date, usage_limit, usage_limit_per_user, priority, stackable, target_role, active) 
                        VALUES (:name, :description, :code, :type, :value, :buy_qty, :get_qty, :target_type, :min_order_amount, :start_date, :end_date, :usage_limit, :usage_limit_per_user, :priority, :stackable, :target_role, :active)";
                $this->db->prepare($sql)->execute($params);
                $promotionId = (int)$this->db->lastInsertId();
            }

            // Sync targets
            $targetIds = $isObject ? $data->target_ids : ($data['target_ids'] ?? []);
            $excludedIds = $isObject ? $data->excluded_ids : ($data['excluded_ids'] ?? []);
            if ($params['target_type'] === Promotion::TARGET_ORDER) {
                $targetIds = [];
                $excludedIds = [];
            }
            $this->syncTargets($promotionId, $targetIds, false);
            $this->syncTargets($promotionId, $excludedIds, true);

            // Sync tiers
            $tiers = $isObject ? $data->tiers : ($data['tiers'] ?? []);
            $this->syncTiers($promotionId, $tiers);

            // Sync additional codes
            $additionalCodes = $isObject ? $data->additional_codes : ($data['additional_codes'] ?? []);
            $this->syncAdditionalCodes($promotionId, $additionalCodes);

            $this->db->commit();
            return $promotionId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void {
        $this->db->prepare("DELETE FROM promotions WHERE id = ?")->execute([$id]);
    }

    public function getActiveAutomaticPromotions(): array {
        return $this->getActivePromotions(true);
    }

    public function getActivePromotions(bool $onlyAutomatic = false): array {
        $sql = "SELECT * FROM promotions 
                WHERE active = 1 
                AND (start_date IS NULL OR start_date <= CURRENT_TIMESTAMP) 
                AND (end_date IS NULL OR end_date >= CURRENT_TIMESTAMP) 
                AND (usage_limit IS NULL OR used_count < usage_limit)";
        
        if ($onlyAutomatic) {
            $sql .= " AND (code IS NULL OR code = '')";
        }

        $sql .= " ORDER BY priority DESC, value DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_CLASS, Promotion::class, [$this->logger]);
        $stmt->execute();
        $promotions = $stmt->fetchAll();

        foreach ($promotions as $promo) {
            $promo->target_ids = $this->getTargetIds($promo->id);
            $promo->excluded_ids = $this->getTargetIds($promo->id, true);
            $promo->tiers = $this->getTiers($promo->id);
            $promo->additional_codes = $this->getAdditionalCodes($promo->id);
        }

        return $promotions;
    }

    public function validateCode(string $code, array $cartItems, float $subtotal, ?\App\Models\User $user = null): ?Promotion {
        $promo = $this->findByCode($code);
        
        if (!$promo || !$promo->isActive($user, $this->isFirstOrder($user))) {
            return null;
        }

        if ($subtotal < $promo->min_order_amount) {
            return null;
        }

        if ($user && $promo->usage_limit_per_user !== null) {
            $count = $this->getUserUsageCount($promo->id, $user->id);
            if ($count >= $promo->usage_limit_per_user) {
                return null;
            }
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

    private function getTargetIds(int $promotionId, bool $isExclusion = false): array {
        $stmt = $this->db->prepare("SELECT target_id FROM promotion_targets WHERE promotion_id = ? AND is_exclusion = ?");
        $stmt->execute([$promotionId, (int)$isExclusion]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function syncTargets(int $promotionId, array $targetIds, bool $isExclusion = false): void {
        $this->db->prepare("DELETE FROM promotion_targets WHERE promotion_id = ? AND is_exclusion = ?")
            ->execute([$promotionId, (int)$isExclusion]);
        
        $targetIds = array_unique(array_filter(array_map('intval', $targetIds)));

        if (empty($targetIds)) {
            return;
        }

        $stmt = $this->db->prepare("INSERT INTO promotion_targets (promotion_id, target_id, is_exclusion) VALUES (?, ?, ?)");
        foreach ($targetIds as $targetId) {
            $stmt->execute([$promotionId, $targetId, (int)$isExclusion]);
        }
    }

    private function getTiers(int $promotionId): array {
        $stmt = $this->db->prepare("SELECT min_amount, value FROM promotion_tiers WHERE promotion_id = ? ORDER BY min_amount ASC");
        $stmt->execute([$promotionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function syncTiers(int $promotionId, array $tiers): void {
        $this->db->prepare("DELETE FROM promotion_tiers WHERE promotion_id = ?")->execute([$promotionId]);
        
        $stmt = $this->db->prepare("INSERT INTO promotion_tiers (promotion_id, min_amount, value) VALUES (?, ?, ?)");
        foreach ($tiers as $tier) {
            if (isset($tier['min_amount']) && isset($tier['value'])) {
                $stmt->execute([$promotionId, (float)$tier['min_amount'], (float)$tier['value']]);
            }
        }
    }

    private function getAdditionalCodes(int $promotionId): array {
        $stmt = $this->db->prepare("SELECT code FROM promotion_codes WHERE promotion_id = ?");
        $stmt->execute([$promotionId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function syncAdditionalCodes(int $promotionId, array $codes): void {
        $this->db->prepare("DELETE FROM promotion_codes WHERE promotion_id = ?")->execute([$promotionId]);
        
        $codes = array_unique(array_filter(array_map('trim', $codes)));
        if (empty($codes)) return;

        $stmt = $this->db->prepare("INSERT INTO promotion_codes (promotion_id, code) VALUES (?, ?)");
        foreach ($codes as $code) {
            $stmt->execute([$promotionId, $code]);
        }
    }

    private function getUserUsageCount(int $promotionId, int $userId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM order_promotions op 
                                    JOIN orders o ON op.order_id = o.id 
                                    WHERE op.promotion_id = ? AND o.user_id = ? AND o.status != 'cancelled'");
        $stmt->execute([$promotionId, $userId]);
        return (int)$stmt->fetchColumn();
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
