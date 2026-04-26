<?php

namespace App\Services;

use App\Models\Product;
use Psr\Log\LoggerInterface;

class ProductService implements ProductServiceInterface {
    public function __construct(
        private \PDO $db,
        private LoggerInterface $logger
    ) {}

    /**
     * Get all products for admin list, optionally filtered by search.
     */
    public function getAllForAdmin(string $search = ''): array {
        if ($search !== '') {
            $stmt = $this->db->prepare(
                "SELECT p.*, c.name as cat_name
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.name LIKE ?
                 ORDER BY p.name ASC"
            );
            $stmt->execute(['%' . $search . '%']);
            return $stmt->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
        }

        return $this->db->query(
            "SELECT p.*, c.name as cat_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             ORDER BY p.name ASC"
        )->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
    }

    /**
     * Find a single product by ID.
     */
    public function findById(int $id): ?Product {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->setFetchMode(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find a single product by slug (for storefront).
     */
    public function findBySlug(string $slug): ?Product {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.name as cat_name, c.slug as cat_slug
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.slug = ? AND p.active = 1"
        );
        $stmt->setFetchMode(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Save (Create or Update) a product.
     */
    public function save(array|Product $data, int $id = 0): int {
        $name = is_array($data) ? $data['name'] : $data->name;
        $slug = slugify($name);

        $params = is_array($data) ? [
            $data['name'], $slug, $data['description'], (float)$data['price'], (float)$data['vat_rate'],
            (int)$data['stock'], $data['category_id'], $data['image'],
            (int)$data['active'], (int)$data['featured']
        ] : [
            $data->name, $slug, $data->description, $data->price, $data->vat_rate,
            $data->stock, $data->category_id, $data->image,
            (int)$data->active, (int)$data->featured
        ];

        if ($id) {
            $check = $this->db->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
            $check->execute([$slug, $id]);
            if ($check->fetch()) $slug .= '-' . $id;
            $params[1] = $slug;

            $this->db->prepare(
                "UPDATE products
                 SET name=?, slug=?, description=?, price=?, vat_rate=?, stock=?, category_id=?, image=?, active=?, featured=?
                 WHERE id=?"
            )->execute([...$params, $id]);
            return $id;
        } else {
            $check = $this->db->prepare("SELECT id FROM products WHERE slug = ?");
            $check->execute([$slug]);
            if ($check->fetch()) $slug .= '-' . time();
            $params[1] = $slug;

            $this->db->prepare(
                "INSERT INTO products (name, slug, description, price, vat_rate, stock, category_id, image, active, featured)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute($params);
            return (int)$this->db->lastInsertId();
        }
    }

    /**
     * Deactivate a product.
     */
    public function deactivate(int $id): void {
        $this->db->prepare("UPDATE products SET active = 0 WHERE id = ?")->execute([$id]);
    }

    /**
     * Get products by multiple IDs.
     */
    public function findByIds(array $ids): array {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
    }

    /**
     * Search products with pagination and sorting.
     */
    public function search(string $query, ?int $perPage, int $currentPage, string $sort): array {
        $like = '%' . $query . '%';
        $order_by = $this->getSortSql($sort);
        
        $sql = "SELECT p.*, c.name as cat_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.active = 1 AND (p.name LIKE ? OR p.description LIKE ?)
                ORDER BY $order_by";
        
        if ($perPage !== null) {
            $offset = ($currentPage - 1) * $perPage;
            $sql .= " LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
    }

    /**
     * Count products matching search query.
     */
    public function countSearch(string $query): int {
        $like = '%' . $query . '%';
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM products p WHERE p.active = 1 AND (p.name LIKE ? OR p.description LIKE ?)");
        $stmt->execute([$like, $like]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get products in categories (including subcategories) with pagination and sorting.
     */
    public function getByCategory(array $categoryIds, ?int $perPage, int $currentPage, string $sort): array {
        if (empty($categoryIds)) return [];
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $order_by = $this->getSortSql($sort);

        $sql = "SELECT p.*, c.name as cat_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.category_id IN ($placeholders) AND p.active = 1
                ORDER BY $order_by";

        if ($perPage !== null) {
            $offset = ($currentPage - 1) * $perPage;
            $sql .= " LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($categoryIds);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
    }

    /**
     * Count products in categories.
     */
    public function countByCategory(array $categoryIds): int {
        if (empty($categoryIds)) return 0;
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE category_id IN ($placeholders) AND active = 1");
        $stmt->execute($categoryIds);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get all active products with pagination and sorting.
     */
    public function getAllActive(?int $perPage, int $currentPage, string $sort): array {
        $order_by = $this->getSortSql($sort);
        $sql = "SELECT p.*, c.name as cat_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.active = 1
                ORDER BY $order_by";

        if ($perPage !== null) {
            $offset = ($currentPage - 1) * $perPage;
            $sql .= " LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
        }

        return $this->db->query($sql)->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
    }

    /**
     * Count all active products.
     */
    public function countAllActive(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM products WHERE active = 1")->fetchColumn();
    }

    /**
     * Get products with low stock.
     */
    public function getLowStock(int $threshold, int $limit = 10): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM products WHERE active = 1 AND stock <= ? ORDER BY stock ASC LIMIT ?"
        );
        $stmt->bindValue(1, $threshold, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
    }

    private function getSortSql(string $sort): string {
        return match($sort) {
            'price_asc'  => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'featured'   => 'p.featured DESC, p.created_at DESC',
            default      => 'p.name',
        };
    }

    /**
     * Get featured products for homepage.
     */
    public function getFeatured(int $limit = 8): array {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.name as cat_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.active = 1 AND p.stock > 0
             ORDER BY p.featured DESC, p.created_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
    }
}
