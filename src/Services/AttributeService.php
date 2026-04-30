<?php
namespace App\Services;

use Psr\Log\LoggerInterface;

class AttributeService implements AttributeServiceInterface {
    public function __construct(
        private \PDO $db,
        private LoggerInterface $logger
    ) {}

    public function getAll(): array {
        return $this->db->query("SELECT * FROM attributes ORDER BY name ASC")->fetchAll(\PDO::FETCH_CLASS, \App\Models\Attribute::class, [$this->logger]);
    }

    public function findById(int $id): ?\App\Models\Attribute {
        $stmt = $this->db->prepare("SELECT * FROM attributes WHERE id = ?");
        $stmt->setFetchMode(\PDO::FETCH_CLASS, \App\Models\Attribute::class, [$this->logger]);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function save(array $data, int $id = 0): int {
        if ($id) {
            $this->db->prepare("UPDATE attributes SET name = ? WHERE id = ?")
                ->execute([$data['name'], $id]);
            return $id;
        } else {
            $this->db->prepare("INSERT INTO attributes (name) VALUES (?)")
                ->execute([$data['name']]);
            return (int)$this->db->lastInsertId();
        }
    }

    public function delete(int $id): void {
        $this->db->prepare("DELETE FROM attributes WHERE id = ?")->execute([$id]);
    }

    public function getValues(int $attributeId): array {
        $stmt = $this->db->prepare("SELECT * FROM attribute_values WHERE attribute_id = ? ORDER BY sort_order ASC, value ASC");
        $stmt->execute([$attributeId]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, \App\Models\AttributeValue::class, [$this->logger]);
    }

    public function saveValue(array $data, int $id = 0): int {
        if ($id) {
            $this->db->prepare("UPDATE attribute_values SET value = ?, sort_order = ? WHERE id = ?")
                ->execute([$data['value'], $data['sort_order'] ?? 0, $id]);
            return $id;
        } else {
            $this->db->prepare("INSERT INTO attribute_values (attribute_id, value, sort_order) VALUES (?, ?, ?)")
                ->execute([$data['attribute_id'], $data['value'], $data['sort_order'] ?? 0]);
            return (int)$this->db->lastInsertId();
        }
    }

    public function deleteValue(int $id): void {
        $this->db->prepare("DELETE FROM attribute_values WHERE id = ?")->execute([$id]);
    }

    public function getProductAttributeValues(int $productId): array {
        $stmt = $this->db->prepare("SELECT attribute_value_id FROM product_attribute_values WHERE product_id = ?");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function saveProductAttributeValues(int $productId, array $valueIds): void {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM product_attribute_values WHERE product_id = ?")->execute([$productId]);
            if (!empty($valueIds)) {
                $stmt = $this->db->prepare("INSERT INTO product_attribute_values (product_id, attribute_value_id) VALUES (?, ?)");
                foreach ($valueIds as $valId) {
                    $stmt->execute([$productId, $valId]);
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getVariantAttributes(int $productId): array {
        $stmt = $this->db->prepare("SELECT attribute_id FROM product_variant_attributes WHERE product_id = ?");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function saveVariantAttributes(int $productId, array $attributeIds): void {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM product_variant_attributes WHERE product_id = ?")->execute([$productId]);
            if (!empty($attributeIds)) {
                $stmt = $this->db->prepare("INSERT INTO product_variant_attributes (product_id, attribute_id) VALUES (?, ?)");
                foreach ($attributeIds as $attrId) {
                    $stmt->execute([$productId, $attrId]);
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getVariantAttributeValues(int $variantId): array {
        $stmt = $this->db->prepare("SELECT attribute_value_id FROM product_variant_attribute_values WHERE variant_id = ?");
        $stmt->execute([$variantId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function saveVariantAttributeValues(int $variantId, array $attributeValueIds): void {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM product_variant_attribute_values WHERE variant_id = ?")->execute([$variantId]);
            if (!empty($attributeValueIds)) {
                $stmt = $this->db->prepare("INSERT INTO product_variant_attribute_values (variant_id, attribute_value_id) VALUES (?, ?)");
                foreach ($attributeValueIds as $valId) {
                    $stmt->execute([$variantId, $valId]);
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
