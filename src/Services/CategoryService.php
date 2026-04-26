<?php

namespace App\Services;

use App\Models\Category;
use Psr\Log\LoggerInterface;

class CategoryService implements CategoryServiceInterface {
    public function __construct(
        private \PDO $db,
        private LoggerInterface $logger
    ) {}

    /**
     * Get all categories with parent names and product counts.
     */
    public function getAllForAdmin(): array {
        return $this->db->query(
            "SELECT c.*, p.name as parent_name, COUNT(pr.id) as product_count
             FROM categories c
             LEFT JOIN categories p ON c.parent_id = p.id
             LEFT JOIN products pr ON pr.category_id = c.id
             GROUP BY c.id
             ORDER BY p.name, c.name"
        )->fetchAll(\PDO::FETCH_CLASS, Category::class, [$this->logger]);
    }

    /**
     * Get all categories (simple list).
     */
    public function getAll(): array {
        return $this->db->query("SELECT * FROM categories ORDER BY name")
            ->fetchAll(\PDO::FETCH_CLASS, Category::class, [$this->logger]);
    }

    /**
     * Find a category by ID.
     */
    public function findById(int $id): ?Category {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->setFetchMode(\PDO::FETCH_CLASS, Category::class, [$this->logger]);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find a category by slug.
     */
    public function findBySlug(string $slug): ?Category {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE slug = ?");
        $stmt->setFetchMode(\PDO::FETCH_CLASS, Category::class, [$this->logger]);
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get categories as a hierarchical tree.
     */
    public function getTree(): array {
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
        return $tree;
    }

    /**
     * Get categories as a flat list with parent names (for forms).
     */
    public function getFlat(): array {
        return $this->db->query(
            "SELECT c.*, p.name as parent_name
             FROM categories c
             LEFT JOIN categories p ON c.parent_id = p.id
             ORDER BY COALESCE(p.name, c.name), c.parent_id IS NOT NULL, c.name"
        )->fetchAll(\PDO::FETCH_CLASS, Category::class, [$this->logger]);
    }

    /**
     * Get subcategories for a given category.
     */
    public function getSubcategories(int $parentId): array {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY name");
        $stmt->execute([$parentId]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Category::class, [$this->logger]);
    }

    /**
     * Get breadcrumb for a given category.
     */
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

    /**
     * Save (Create or Update) a category.
     */
    public function save(array|Category $data, int $id = 0): int {
        $name = is_array($data) ? $data['name'] : $data->name;
        $slug = slugify($name);

        $params = is_array($data) ? [
            $data['name'], $slug, $data['parent_id'], $data['description'], $data['icon']
        ] : [
            $data->name, $slug, $data->parent_id, $data->description, $data->icon
        ];

        if ($id) {
            $check = $this->db->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
            $check->execute([$slug, $id]);
            if ($check->fetch()) $slug .= '-' . $id;
            $params[1] = $slug;

            $this->db->prepare(
                "UPDATE categories SET name=?, slug=?, parent_id=?, description=?, icon=? WHERE id=?"
            )->execute([...$params, $id]);
            return $id;
        } else {
            $check = $this->db->prepare("SELECT id FROM categories WHERE slug = ?");
            $check->execute([$slug]);
            if ($check->fetch()) $slug .= '-' . time();
            $params[1] = $slug;

            $this->db->prepare(
                "INSERT INTO categories (name, slug, parent_id, description, icon) VALUES (?, ?, ?, ?, ?)"
            )->execute($params);
            return (int)$this->db->lastInsertId();
        }
    }

    /**
     * Delete a category.
     */
    public function delete(int $id): void {
        // Move children to root
        $this->db->prepare("UPDATE categories SET parent_id = NULL WHERE parent_id = ?")->execute([$id]);
        // Remove from products
        $this->db->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?")->execute([$id]);
        // Delete category
        $this->db->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    }
}
