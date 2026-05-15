<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Repositories\ProductRepositoryInterface;

class ProductVariantService implements ProductVariantServiceInterface {
    public function __construct(
        private ProductRepositoryInterface $repository,
        private AttributeServiceInterface $attributeService
    ) {}

    public function getVariants(int $productId): array {
        $variants = $this->repository->getVariants($productId);
        foreach ($variants as $variant) {
            $variant->attribute_value_ids = $this->attributeService->getVariantAttributeValues($variant->id);
        }
        return $variants;
    }

    public function findById(int $id): ?ProductVariant {
        return $this->repository->findVariantById($id);
    }

    public function findByIds(array $ids): array {
        return $this->repository->findVariantsByIds($ids);
    }

    public function save(array $data, int $id = 0): int {
        return $this->repository->saveVariant($data, $id);
    }

    public function delete(int $id): void {
        $this->repository->deleteVariant($id);
    }
}
