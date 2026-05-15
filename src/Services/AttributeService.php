<?php
namespace App\Services;

use App\Repositories\AttributeRepositoryInterface;
use Psr\Log\LoggerInterface;

class AttributeService implements AttributeServiceInterface {
    public function __construct(
        private AttributeRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}

    public function getAll(): array {
        return $this->repository->getAll();
    }

    public function findById(int $id): ?\App\Models\Attribute {
        return $this->repository->findById($id);
    }

    public function save(array $data, int $id = 0): int {
        return $this->repository->save($data, $id);
    }

    public function delete(int $id): void {
        $this->repository->delete($id);
    }

    public function getValues(int $attributeId): array {
        return $this->repository->getValues($attributeId);
    }

    public function saveValue(array $data, int $id = 0): int {
        return $this->repository->saveValue($data, $id);
    }

    public function deleteValue(int $id): void {
        $this->repository->deleteValue($id);
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
