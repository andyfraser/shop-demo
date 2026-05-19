<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepositoryInterface;
use Psr\Log\LoggerInterface;
use App\Core\Cache\CacheInterface;

class CategoryService implements CategoryServiceInterface {
    public function __construct(
        private CategoryRepositoryInterface $repository,
        private LoggerInterface $logger,
        private CacheInterface $cache
    ) {}

    public function getAllForAdmin(): array {
        return $this->repository->getAllForAdmin();
    }

    public function getAll(): array {
        return $this->repository->getAll();
    }

    public function findById(int $id): ?Category {
        return $this->repository->findById($id);
    }

    public function findBySlug(string $slug): ?Category {
        return $this->repository->findBySlug($slug);
    }

    public function getTree(): array {
        $cacheKey = 'category_tree';
        $tree = $this->cache->get($cacheKey);

        if ($tree !== null) {
            return $tree;
        }

        $all = $this->getAll();
        $tree = [];
        $map = [];
        foreach ($all as $c) {
            $c->children = [];
            $map[$c->id] = $c;
        }
        foreach ($map as $c) {
            if ($c->parent_id) $map[$c->parent_id]->children[] = $c;
            else $tree[] = $c;
        }

        $this->cache->set($cacheKey, $tree, 86400); // Cache for 24h
        return $tree;
    }

    public function getFlat(): array {
        return $this->repository->getFlat();
    }

    public function getSubcategories(int $parentId): array {
        return $this->repository->getSubcategories($parentId);
    }

    public function getBreadcrumb(int $categoryId): array {
        $crumbs = [];
        $all = $this->getAll();
        $map = [];
        foreach ($all as $c) $map[$c->id] = $c;

        $current = $map[$categoryId] ?? null;
        while ($current) {
            array_unshift($crumbs, $current);
            $current = $current->parent_id ? ($map[$current->parent_id] ?? null) : null;
        }
        return $crumbs;
    }

    public function save(array|Category $data, int $id = 0): int {
        $result = $this->repository->save($data, $id);
        $this->cache->delete('category_tree');
        return $result;
    }

    public function delete(int $id): void {
        $this->repository->delete($id);
        $this->cache->delete('category_tree');
    }
}
