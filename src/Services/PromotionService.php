<?php

namespace App\Services;

use App\Models\Promotion;
use Psr\Log\LoggerInterface;
use PDO;

class PromotionService implements PromotionServiceInterface {
    public function __construct(
        private PDO $db,
        private LoggerInterface $logger
    ) {}

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
        }

        return $promotion;
    }

    public function findByCode(string $code): ?Promotion {
        $stmt = $this->db->prepare("SELECT * FROM promotions WHERE code = ?");
        $stmt->setFetchMode(PDO::FETCH_CLASS, Promotion::class, [$this->logger]);
        $stmt->execute([$code]);
        $promotion = $stmt->fetch() ?: null;

        if ($promotion) {
            $promotion->target_ids = $this->getTargetIds($promotion->id);
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
            'active' => isset($data['active']) ? (int)$data['active'] : 1
        ];

        try {
            $this->db->beginTransaction();

            if ($id > 0) {
                $sql = "UPDATE promotions SET name = :name, description = :description, code = :code, type = :type, 
                        value = :value, buy_qty = :buy_qty, get_qty = :get_qty, target_type = :target_type, 
                        min_order_amount = :min_order_amount, start_date = :start_date, end_date = :end_date, 
                        usage_limit = :usage_limit, active = :active 
                        WHERE id = :id";
                $params['id'] = $id;
                $this->db->prepare($sql)->execute($params);
                $promotionId = $id;
            } else {
                $sql = "INSERT INTO promotions (name, description, code, type, value, buy_qty, get_qty, target_type, min_order_amount, start_date, end_date, usage_limit, active) 
                        VALUES (:name, :description, :code, :type, :value, :buy_qty, :get_qty, :target_type, :min_order_amount, :start_date, :end_date, :usage_limit, :active)";
                $this->db->prepare($sql)->execute($params);
                $promotionId = (int)$this->db->lastInsertId();
            }

            // Sync targets
            $targetIds = $isObject ? $data->target_ids : ($data['target_ids'] ?? []);
            if ($params['target_type'] === Promotion::TARGET_ORDER) {
                $targetIds = [];
            }
            $this->syncTargets($promotionId, $targetIds);

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

        $sql .= " ORDER BY value DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_CLASS, Promotion::class, [$this->logger]);
        $stmt->execute();
        $promotions = $stmt->fetchAll();

        foreach ($promotions as $promo) {
            $promo->target_ids = $this->getTargetIds($promo->id);
        }

        return $promotions;
    }

    public function validateCode(string $code, array $cartItems, float $subtotal): ?Promotion {
        $promo = $this->findByCode($code);
        
        if (!$promo || !$promo->isActive()) {
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
        if (!$promotion->isActive()) {
            return 0.0;
        }

        if ($subtotal < $promotion->min_order_amount) {
            return 0.0;
        }

        $discount = 0.0;

        if ($promotion->target_type === Promotion::TARGET_ORDER) {
            if ($promotion->type === Promotion::TYPE_PERCENTAGE) {
                $discount = $subtotal * ($promotion->value / 100);
            } elseif ($promotion->type === Promotion::TYPE_FIXED_AMOUNT) {
                $discount = $promotion->value;
            } elseif ($promotion->type === Promotion::TYPE_BUY_X_GET_Y) {
                $qualifyingPrices = [];
                foreach ($cartItems as $item) {
                    for ($i = 0; $i < $item->qty; $i++) {
                        $qualifyingPrices[] = $item->unit_price;
                    }
                }
                $discount = $this->calculateBogoDiscount($promotion, $qualifyingPrices);
            }
        } else {
            // Product or Category specific
            $qualifyingPrices = [];
            foreach ($cartItems as $item) {
                if ($this->itemMatchesTarget($item, $promotion)) {
                    if ($promotion->type === Promotion::TYPE_PERCENTAGE) {
                        $discount += ($item->unit_price * $item->qty) * ($promotion->value / 100);
                    } elseif ($promotion->type === Promotion::TYPE_FIXED_AMOUNT) {
                        $discount += $promotion->value * $item->qty;
                    } elseif ($promotion->type === Promotion::TYPE_BUY_X_GET_Y) {
                        for ($i = 0; $i < $item->qty; $i++) {
                            $qualifyingPrices[] = $item->unit_price;
                        }
                    }
                }
            }

            if ($promotion->type === Promotion::TYPE_BUY_X_GET_Y) {
                $discount = $this->calculateBogoDiscount($promotion, $qualifyingPrices);
            }
        }

        // Cap discount at subtotal
        return min($discount, $subtotal);
    }

    private function calculateBogoDiscount(Promotion $promotion, array $prices): float {
        if (empty($prices)) {
            return 0.0;
        }

        sort($prices); // Cheapest first
        $totalUnits = count($prices);
        $bundleSize = $promotion->buy_qty + $promotion->get_qty;
        
        if ($bundleSize <= 0) {
            return 0.0;
        }

        $discountUnits = floor($totalUnits / $bundleSize) * $promotion->get_qty;
        $discount = 0.0;

        for ($i = 0; $i < $discountUnits; $i++) {
            $discount += $prices[$i] * ($promotion->value / 100);
        }

        return $discount;
    }

    private function getTargetIds(int $promotionId): array {
        $stmt = $this->db->prepare("SELECT target_id FROM promotion_targets WHERE promotion_id = ?");
        $stmt->execute([$promotionId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function syncTargets(int $promotionId, array $targetIds): void {
        $this->db->prepare("DELETE FROM promotion_targets WHERE promotion_id = ?")->execute([$promotionId]);
        
        $targetIds = array_unique(array_filter(array_map('intval', $targetIds)));

        if (empty($targetIds)) {
            return;
        }

        $stmt = $this->db->prepare("INSERT INTO promotion_targets (promotion_id, target_id) VALUES (?, ?)");
        foreach ($targetIds as $targetId) {
            $stmt->execute([$promotionId, $targetId]);
        }
    }

    private function itemMatchesTarget(\App\Models\CartItem $item, Promotion $promotion): bool {
        if ($promotion->target_type === Promotion::TARGET_PRODUCT) {
            return in_array($item->product_id, $promotion->target_ids);
        }
        
        if ($promotion->target_type === Promotion::TARGET_CATEGORY) {
            return in_array($item->product->category_id, $promotion->target_ids);
        }

        return false;
    }
}
