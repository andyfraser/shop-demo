<?php
namespace App\Services;

use App\Repositories\AttributeRepositoryInterface;
use Psr\Log\LoggerInterface;

class AttributeService implements AttributeServiceInterface {
    public function __construct(
        private AttributeRepositoryInterface $repository,
        private LoggerInterface $logger,
        private \App\Core\Cache\CacheInterface $cache
    ) {}

    public function getAll(): array {
        $cached = $this->cache->get('all_attributes');
        if ($cached !== null) return $cached;

        $attributes = $this->repository->getAll();
        $this->cache->set('all_attributes', $attributes, 86400);
        return $attributes;
    }

    public function findById(int $id): ?\App\Models\Attribute {
        return $this->repository->findById($id);
    }

    public function save(array $data, int $id = 0): int {
        $result = $this->repository->save($data, $id);
        $this->cache->delete('all_attributes');
        return $result;
    }

    public function delete(int $id): void {
        $this->repository->delete($id);
        $this->cache->delete('all_attributes');
    }

    public function getValues(int $attributeId): array {
        $cacheKey = "attribute_values_$attributeId";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) return $cached;

        $values = $this->repository->getValues($attributeId);
        $this->cache->set($cacheKey, $values, 86400);
        return $values;
    }

    public function saveValue(array $data, int $id = 0): int {
        $result = $this->repository->saveValue($data, $id);
        if (!empty($data['attribute_id'])) {
            $this->cache->delete("attribute_values_" . $data['attribute_id']);
        }
        return $result;
    }

    public function deleteValue(int $id): void {
        $stmt = \App\Core\Database::getConnection()->prepare("SELECT attribute_id FROM attribute_values WHERE id = ?");
        $stmt->execute([$id]);
        $attrId = $stmt->fetchColumn();
        
        $this->repository->deleteValue($id);
        
        if ($attrId) {
            $this->cache->delete("attribute_values_$attrId");
        }
    }

    public function getProductAttributeValues(int $productId): array {
        return $this->repository->getProductAttributeValues($productId);
    }

    public function saveProductAttributeValues(int $productId, array $valueIds): void {
        $this->repository->saveProductAttributeValues($productId, $valueIds);
    }

    public function getVariantAttributes(int $productId): array {
        return $this->repository->getVariantAttributes($productId);
    }

    public function saveVariantAttributes(int $productId, array $attributeIds): void {
        $this->repository->saveVariantAttributes($productId, $attributeIds);
    }

    public function getVariantAttributeValues(int $variantId): array {
        return $this->repository->getVariantAttributeValues($variantId);
    }

    public function saveVariantAttributeValues(int $variantId, array $attributeValueIds): void {
        $this->repository->saveVariantAttributeValues($variantId, $attributeValueIds);
    }
}
