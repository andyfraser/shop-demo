<?php
namespace App\Repositories;

use App\Models\Category;

interface CategoryRepositoryInterface {
    public function getAllForAdmin(): array;
    public function getAll(): array;
    public function findById(int $id): ?Category;
    public function findBySlug(string $slug): ?Category;
    public function getFlat(): array;
    public function getSubcategories(int $parentId): array;
    public function save(array|Category $data, int $id = 0): int;
    public function delete(int $id): void;
}
