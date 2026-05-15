<?php
namespace App\Repositories;

use App\Models\Promotion;
use Psr\Log\LoggerInterface;
use PDO;

class PromotionRepository implements PromotionRepositoryInterface {
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
        return $stmt->fetch() ?: null;
    }

    public function findByCode(string $code): ?Promotion {
        $stmt = $this->db->prepare("SELECT p.* FROM promotions p 
                                    LEFT JOIN promotion_codes pc ON p.id = pc.promotion_id 
                                    WHERE p.code = ? OR pc.code = ?");
        $stmt->setFetchMode(PDO::FETCH_CLASS, Promotion::class, [$this->logger]);
        $stmt->execute([$code, $code]);
        return $stmt->fetch() ?: null;
    }

    public function save(array $params, int $id = 0): int {
        if ($id > 0) {
            $sql = "UPDATE promotions SET name = :name, description = :description, code = :code, type = :type, 
                    value = :value, buy_qty = :buy_qty, get_qty = :get_qty, target_type = :target_type, 
                    min_order_amount = :min_order_amount, start_date = :start_date, end_date = :end_date, 
                    usage_limit = :usage_limit, usage_limit_per_user = :usage_limit_per_user, 
                    priority = :priority, stackable = :stackable, target_role = :target_role, active = :active 
                    WHERE id = :id";
            $params['id'] = $id;
            $this->db->prepare($sql)->execute($params);
            return $id;
        } else {
            $sql = "INSERT INTO promotions (name, description, code, type, value, buy_qty, get_qty, target_type, min_order_amount, start_date, end_date, usage_limit, usage_limit_per_user, priority, stackable, target_role, active) 
                    VALUES (:name, :description, :code, :type, :value, :buy_qty, :get_qty, :target_type, :min_order_amount, :start_date, :end_date, :usage_limit, :usage_limit_per_user, :priority, :stackable, :target_role, :active)";
            $this->db->prepare($sql)->execute($params);
            return (int)$this->db->lastInsertId();
        }
    }

    public function delete(int $id): void {
        $this->db->prepare("DELETE FROM promotions WHERE id = ?")->execute([$id]);
    }

    public function getActivePromotions(string $now, bool $onlyAutomatic = false): array {
        $sql = "SELECT * FROM promotions 
                WHERE active = 1 
                AND (start_date IS NULL OR start_date <= ?) 
                AND (end_date IS NULL OR end_date >= ?) 
                AND (usage_limit IS NULL OR used_count < usage_limit)";
        
        if ($onlyAutomatic) {
            $sql .= " AND (code IS NULL OR code = '')";
        }

        $sql .= " ORDER BY priority DESC, value DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_CLASS, Promotion::class, [$this->logger]);
        $stmt->execute([$now, $now]);
        return $stmt->fetchAll();
    }

    public function getTargetIds(int $promotionId, bool $isExclusion = false): array {
        $stmt = $this->db->prepare("SELECT target_id FROM promotion_targets WHERE promotion_id = ? AND is_exclusion = ?");
        $stmt->execute([$promotionId, (int)$isExclusion]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function syncTargets(int $promotionId, array $targetIds, bool $isExclusion = false): void {
        $this->db->prepare("DELETE FROM promotion_targets WHERE promotion_id = ? AND is_exclusion = ?")
            ->execute([$promotionId, (int)$isExclusion]);
        
        $targetIds = array_unique(array_filter(array_map('intval', $targetIds)));

        if (empty($targetIds)) return;

        $stmt = $this->db->prepare("INSERT INTO promotion_targets (promotion_id, target_id, is_exclusion) VALUES (?, ?, ?)");
        foreach ($targetIds as $targetId) {
            $stmt->execute([$promotionId, $targetId, (int)$isExclusion]);
        }
    }

    public function getTiers(int $promotionId): array {
        $stmt = $this->db->prepare("SELECT min_amount, value FROM promotion_tiers WHERE promotion_id = ? ORDER BY min_amount ASC");
        $stmt->execute([$promotionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function syncTiers(int $promotionId, array $tiers): void {
        $this->db->prepare("DELETE FROM promotion_tiers WHERE promotion_id = ?")->execute([$promotionId]);
        
        $stmt = $this->db->prepare("INSERT INTO promotion_tiers (promotion_id, min_amount, value) VALUES (?, ?, ?)");
        foreach ($tiers as $tier) {
            if (isset($tier['min_amount']) && isset($tier['value'])) {
                $stmt->execute([$promotionId, (float)$tier['min_amount'], (float)$tier['value']]);
            }
        }
    }

    public function getAdditionalCodes(int $promotionId): array {
        $stmt = $this->db->prepare("SELECT code FROM promotion_codes WHERE promotion_id = ?");
        $stmt->execute([$promotionId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function syncAdditionalCodes(int $promotionId, array $codes): void {
        $this->db->prepare("DELETE FROM promotion_codes WHERE promotion_id = ?")->execute([$promotionId]);
        
        $codes = array_unique(array_filter(array_map('trim', $codes)));
        if (empty($codes)) return;

        $stmt = $this->db->prepare("INSERT INTO promotion_codes (promotion_id, code) VALUES (?, ?)");
        foreach ($codes as $code) {
            $stmt->execute([$promotionId, $code]);
        }
    }

    public function getUserUsageCount(int $promotionId, int $userId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM order_promotions op 
                                    JOIN orders o ON op.order_id = o.id 
                                    WHERE op.promotion_id = ? AND o.user_id = ? AND o.status != 'cancelled'");
        $stmt->execute([$promotionId, $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function beginTransaction(): void { $this->db->beginTransaction(); }
    public function commit(): void { $this->db->commit(); }
    public function rollBack(): void { $this->db->rollBack(); }
}
