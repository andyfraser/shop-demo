<?php
namespace App\Services;

use App\Models\ProductVariant;

interface ProductVariantServiceInterface {
    /**
     * @return ProductVariant[]
     */
    public function getVariants(int $productId): array;
    
    public function findById(int $id): ?ProductVariant;
    
    /**
     * @return ProductVariant[]
     */
    public function findByIds(array $ids): array;
    
    public function save(array $data, int $id = 0): int;
    
    public function delete(int $id): void;
}
