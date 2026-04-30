<?php
namespace App\Services;

interface AttributeServiceInterface {
    public function getAll(): array;
    public function findById(int $id): ?\App\Models\Attribute;
    public function save(array $data, int $id = 0): int;
    public function delete(int $id): void;
    
    public function getValues(int $attributeId): array;
    public function saveValue(array $data, int $id = 0): int;
    public function deleteValue(int $id): void;
    
    public function getProductAttributeValues(int $productId): array;
    public function saveProductAttributeValues(int $productId, array $valueIds): void;

    public function getVariantAttributes(int $productId): array;
    public function saveVariantAttributes(int $productId, array $attributeIds): void;
    
    public function getVariantAttributeValues(int $variantId): array;
    public function saveVariantAttributeValues(int $variantId, array $attributeValueIds): void;
}
