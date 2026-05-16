<?php
namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductVariant;
use Psr\Log\LoggerInterface;

class ProductRepository implements ProductRepositoryInterface {
    public function __construct(
        private \PDO $db,
        private LoggerInterface $logger
    ) {}

    public function getAllForAdmin(\App\Core\QueryCriteria $criteria): array {
        $search = $criteria->getSearchTerm();
        $variantStockSql = "(SELECT COALESCE(SUM(pv.stock), 0) FROM product_variants pv WHERE pv.product_id = p.id AND pv.active = 1)";
        
        if ($search !== '') {
            $stmt = $this->db->prepare(
                "SELECT p.*, c.name as cat_name, $variantStockSql as variant_stock
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.name LIKE ?
                 ORDER BY p.name ASC"
            );
            $stmt->execute(['%' . $search . '%']);
            return $stmt->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
        }

        return $this->db->query(
            "SELECT p.*, c.name as cat_name, $variantStockSql as variant_stock
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             ORDER BY p.name ASC"
        )->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
    }

    public function findById(int $id): ?Product {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->setFetchMode(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

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

    public function save(array|Product $data, int $id = 0): int {
        $name = is_array($data) ? $data['name'] : $data->name;
        $slug = slugify($name);

        $params = is_array($data) ? [
            $data['name'], $slug, $data['sku'] ?? null, $data['description'] ?? null, (float)$data['price'], (float)$data['vat_rate'],
            (int)$data['stock'], $data['category_id'] ?? null, $data['image'] ?? null,
            (int)($data['active'] ?? 1), (int)($data['featured'] ?? 0), (int)($data['force_variant'] ?? 0)
        ] : [
            $data->name, $slug, $data->sku, $data->description, $data->price, $data->vat_rate,
            $data->stock, $data->category_id, $data->image,
            (int)$data->active, (int)$data->featured, (int)$data->force_variant
        ];

        if ($id) {
            $stmt = $this->db->prepare("SELECT id FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $exists = $stmt->fetch();

            $check = $this->db->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
            $check->execute([$slug, $id]);
            if ($check->fetch()) $slug .= '-' . $id;
            $params[1] = $slug;

            if ($exists) {
                $this->db->prepare(
                    "UPDATE products
                     SET name=?, slug=?, sku=?, description=?, price=?, vat_rate=?, stock=?, category_id=?, image=?, active=?, featured=?, force_variant=?
                     WHERE id=?"
                )->execute([...$params, $id]);
            } else {
                $this->db->prepare(
                    "INSERT INTO products (name, slug, sku, description, price, vat_rate, stock, category_id, image, active, featured, force_variant, id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                )->execute([...$params, $id]);
            }
            return $id;
        } else {
            $check = $this->db->prepare("SELECT id FROM products WHERE slug = ?");
            $check->execute([$slug]);
            if ($check->fetch()) $slug .= '-' . time();
            $params[1] = $slug;

            $this->db->prepare(
                "INSERT INTO products (name, slug, sku, description, price, vat_rate, stock, category_id, image, active, featured, force_variant)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute($params);
            return (int)$this->db->lastInsertId();
        }
    }

    public function updateStock(int $id, int $newStock): void {
        $this->db->prepare("UPDATE products SET stock = ? WHERE id = ?")->execute([$newStock, $id]);
    }

    public function deactivate(int $id): void {
        $this->db->prepare("UPDATE products SET active = 0 WHERE id = ?")->execute([$id]);
    }

    public function findByIds(array $ids): array {
        if (empty($ids)) return [];
        $ids = array_values($ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
    }

    public function search(\App\Core\QueryCriteria $criteria): array {
        $query = $criteria->getSearchTerm();
        $normalized = $this->normalizeQuery($query);
        $params = ['%' . $normalized . '%', '%' . $normalized . '%'];
        $order_by = $this->getSortSql($criteria->getSort());
        
        $searchField = $this->getSearchableFieldSql('p.name');
        $descField   = $this->getSearchableFieldSql('p.description');
        
        $sql = "SELECT p.*, c.name as cat_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.active = 1 AND ($searchField LIKE ? OR $descField LIKE ?)";
        
        $this->applyFilters($sql, $params, $criteria->getFilters());
        
        $sql .= " ORDER BY $order_by";
        
        if ($criteria->getLimit() !== null) {
            $sql .= " LIMIT " . (int)$criteria->getLimit() . " OFFSET " . (int)$criteria->getOffset();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
    }

    public function countSearch(\App\Core\QueryCriteria $criteria): int {
        $query = $criteria->getSearchTerm();
        $normalized = $this->normalizeQuery($query);
        $params = ['%' . $normalized . '%', '%' . $normalized . '%'];
        
        $searchField = $this->getSearchableFieldSql('p.name');
        $descField   = $this->getSearchableFieldSql('p.description');
        
        $sql = "SELECT COUNT(*) FROM products p WHERE p.active = 1 AND ($searchField LIKE ? OR $descField LIKE ?)";
        
        $this->applyFilters($sql, $params, $criteria->getFilters());
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getByCategory(array $categoryIds, \App\Core\QueryCriteria $criteria): array {
        if (empty($categoryIds)) return [];
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $params = $categoryIds;
        $order_by = $this->getSortSql($criteria->getSort());

        $sql = "SELECT p.*, c.name as cat_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.category_id IN ($placeholders) AND p.active = 1";

        $this->applyFilters($sql, $params, $criteria->getFilters());

        $sql .= " ORDER BY $order_by";

        if ($criteria->getLimit() !== null) {
            $sql .= " LIMIT " . (int)$criteria->getLimit() . " OFFSET " . (int)$criteria->getOffset();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
    }

    public function countByCategory(array $categoryIds, \App\Core\QueryCriteria $criteria): int {
        if (empty($categoryIds)) return 0;
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $params = $categoryIds;
        $sql = "SELECT COUNT(*) FROM products p WHERE p.category_id IN ($placeholders) AND p.active = 1";
        
        $this->applyFilters($sql, $params, $criteria->getFilters());
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getAllActive(\App\Core\QueryCriteria $criteria): array {
        $params = [];
        $order_by = $this->getSortSql($criteria->getSort());
        $sql = "SELECT p.*, c.name as cat_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.active = 1";

        $this->applyFilters($sql, $params, $criteria->getFilters());

        $sql .= " ORDER BY $order_by";

        if ($criteria->getLimit() !== null) {
            $sql .= " LIMIT " . (int)$criteria->getLimit() . " OFFSET " . (int)$criteria->getOffset();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
    }

    public function countAllActive(\App\Core\QueryCriteria $criteria): int {
        $params = [];
        $sql = "SELECT COUNT(*) FROM products p WHERE p.active = 1";
        $this->applyFilters($sql, $params, $criteria->getFilters());
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getAvailableFilters(array $categoryIds = [], string $query = ''): array {
        $params = [];
        $where = "p.active = 1";
        
        if (!empty($categoryIds)) {
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $where .= " AND p.category_id IN ($placeholders)";
            foreach ($categoryIds as $id) $params[] = $id;
        }
        
        if ($query !== '') {
            $where .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $params[] = '%' . $query . '%';
            $params[] = '%' . $query . '%';
        }

        // Get Min/Max prices
        $priceStmt = $this->db->prepare("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products p WHERE $where");
        $priceStmt->execute($params);
        $prices = $priceStmt->fetch(\PDO::FETCH_ASSOC);

        // Get Attributes and their values present in this result set
        $attrSql = "
            SELECT a.id as attr_id, a.name as attr_name, av.id as val_id, av.value as val_name, av.sort_order as val_sort, COUNT(DISTINCT p.id) as count
            FROM attributes a
            JOIN attribute_values av ON a.id = av.attribute_id
            JOIN product_attribute_values pav ON av.id = pav.attribute_value_id
            JOIN products p ON pav.product_id = p.id
            LEFT JOIN product_variant_attributes pva ON p.id = pva.product_id AND a.id = pva.attribute_id
            LEFT JOIN product_variants pv ON p.id = pv.product_id AND pv.active = 1
            LEFT JOIN product_variant_attribute_values pvav ON pv.id = pvav.variant_id AND av.id = pvav.attribute_value_id
            WHERE $where 
              AND (p.force_variant = 0 OR pva.attribute_id IS NULL OR pvav.attribute_value_id IS NOT NULL)
            GROUP BY a.id, a.name, av.id, av.value, av.sort_order
            ORDER BY a.name ASC, av.sort_order ASC, av.value ASC
        ";
        
        $attrStmt = $this->db->prepare($attrSql);
        $attrStmt->execute($params);
        $rows = $attrStmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $attributes = [];
        foreach ($rows as $row) {
            $attrId = $row['attr_id'];
            if (!isset($attributes[$attrId])) {
                $attributes[$attrId] = [
                    'id' => $attrId,
                    'name' => $row['attr_name'],
                    'values' => []
                ];
            }
            $attributes[$attrId]['values'][] = [
                'id' => $row['val_id'],
                'name' => $row['val_name'],
                'count' => $row['count']
            ];
        }

        return [
            'min_price' => (float)($prices['min_price'] ?? 0),
            'max_price' => (float)($prices['max_price'] ?? 0),
            'attributes' => array_values($attributes)
        ];
    }

    public function getLowStock(int $threshold, int $limit = 1000, string $sort = 'name'): array {
        $isMysql = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql';
        $concat = $isMysql 
            ? "CONCAT(p.name, ' - ', pv.name)" 
            : "p.name || ' - ' || pv.name";

        $orderSql = ($sort === 'stock') ? "p.stock ASC, p.name ASC" : "p.name ASC";
        $vOrderSql = ($sort === 'stock') ? "pv.stock ASC, p.name ASC, pv.name ASC" : "p.name ASC, pv.name ASC";

        // Query for products (only if not force_variant or total stock is low)
        $pStmt = $this->db->prepare(
            "SELECT p.* FROM products p 
             WHERE p.active = 1 AND p.force_variant = 0 AND p.stock <= ?
             ORDER BY $orderSql LIMIT ?"
        );
        $pStmt->bindValue(1, $threshold, \PDO::PARAM_INT);
        $pStmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $pStmt->execute();
        $products = $pStmt->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);

        // Query for variants
        $vStmt = $this->db->prepare(
            "SELECT pv.*, $concat as name, p.name as product_name 
             FROM product_variants pv
             JOIN products p ON pv.product_id = p.id
             WHERE pv.active = 1 AND p.active = 1 AND pv.stock <= ?
             ORDER BY $vOrderSql LIMIT ?"
        );
        $vStmt->bindValue(1, $threshold, \PDO::PARAM_INT);
        $vStmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $vStmt->execute();
        $variants = $vStmt->fetchAll(\PDO::FETCH_CLASS, ProductVariant::class, [$this->logger]);

        // Merge and sort
        $all = array_merge($products, $variants);
        
        if ($sort === 'stock') {
            usort($all, function($a, $b) {
                if ($a->stock === $b->stock) {
                    return strcasecmp($a->name, $b->name);
                }
                return $a->stock <=> $b->stock; // ASC
            });
        } else {
            usort($all, fn($a, $b) => strcasecmp($a->name, $b->name));
        }

        return array_slice($all, 0, $limit);
    }

    public function countLowStock(int $threshold): int {
        $pStmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE active = 1 AND force_variant = 0 AND stock <= ?");
        $pStmt->execute([$threshold]);
        $pCount = (int)$pStmt->fetchColumn();

        $vStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM product_variants pv
             JOIN products p ON pv.product_id = p.id
             WHERE pv.active = 1 AND p.active = 1 AND pv.stock <= ?"
        );
        $vStmt->execute([$threshold]);
        $vCount = (int)$vStmt->fetchColumn();

        return $pCount + $vCount;
    }

    public function getFeatured(int $limit = 8): array {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.name as cat_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.active = 1 AND (p.stock > 0 OR EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id AND pv.stock > 0 AND pv.active = 1))
             ORDER BY p.featured DESC, p.created_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
    }

    public function getVariants(int $productId): array {
        $stmt = $this->db->prepare("SELECT * FROM product_variants WHERE product_id = ? AND active = 1 ORDER BY sort_order ASC, name ASC");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, ProductVariant::class, [$this->logger]);
    }

    public function findVariantById(int $variantId): ?ProductVariant {
        $stmt = $this->db->prepare("SELECT * FROM product_variants WHERE id = ?");
        $stmt->setFetchMode(\PDO::FETCH_CLASS, ProductVariant::class, [$this->logger]);
        $stmt->execute([$variantId]);
        return $stmt->fetch() ?: null;
    }

    public function findVariantsByIds(array $ids): array {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT * FROM product_variants WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, ProductVariant::class, [$this->logger]);
    }

    public function saveVariant(array $data, int $id = 0): int {
        $params = [
            (int)$data['product_id'], $data['name'], $data['sku'] ?? null,
            isset($data['price']) && $data['price'] !== '' ? (float)$data['price'] : null,
            (int)$data['stock'], (int)($data['active'] ?? 1), (int)($data['sort_order'] ?? 0)
        ];

        if ($id) {
            $this->db->prepare(
                "UPDATE product_variants SET product_id=?, name=?, sku=?, price=?, stock=?, active=?, sort_order=? WHERE id=?"
            )->execute([...$params, $id]);
            return $id;
        } else {
            $this->db->prepare(
                "INSERT INTO product_variants (product_id, name, sku, price, stock, active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)"
            )->execute($params);
            return (int)$this->db->lastInsertId();
        }
    }

    public function updateVariantStock(int $id, int $newStock): void {
        $this->db->prepare("UPDATE product_variants SET stock = ? WHERE id = ?")->execute([$newStock, $id]);
    }

    public function deleteVariant(int $id): void {
        $this->db->prepare("DELETE FROM product_variants WHERE id = ?")->execute([$id]);
    }

    public function getRelatedProducts(int $productId, int $limit = 4): array {
        $product = $this->findById($productId);
        if (!$product) return [];

        $sql = "
            SELECT p.*, 
                   (
                       (CASE WHEN p.category_id = ? THEN 10 ELSE 0 END) +
                       (CASE WHEN p.featured = 1 THEN 2 ELSE 0 END) +
                       COALESCE(shared_attrs.attr_count * 5, 0)
                   ) as relevance_score
            FROM products p
            LEFT JOIN (
                SELECT pav2.product_id, COUNT(*) as attr_count
                FROM product_attribute_values pav1
                JOIN product_attribute_values pav2 ON pav1.attribute_value_id = pav2.attribute_value_id
                WHERE pav1.product_id = ? AND pav2.product_id != ?
                GROUP BY pav2.product_id
            ) shared_attrs ON p.id = shared_attrs.product_id
            WHERE p.id != ? AND p.active = 1 AND (p.stock > 0 OR EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id AND pv.stock > 0 AND pv.active = 1))
            ORDER BY relevance_score DESC, p.created_at DESC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $product->category_id, \PDO::PARAM_INT);
        $stmt->bindValue(2, $productId, \PDO::PARAM_INT);
        $stmt->bindValue(3, $productId, \PDO::PARAM_INT);
        $stmt->bindValue(4, $productId, \PDO::PARAM_INT);
        $stmt->bindValue(5, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
    }

    public function searchSuggestions(string $query, int $limit = 5): array {
        $normalized = $this->normalizeQuery($query);
        $searchField = $this->getSearchableFieldSql('name');
        
        $stmt = $this->db->prepare(
            "SELECT * FROM products 
             WHERE active = 1 AND $searchField LIKE ? 
             ORDER BY featured DESC, name ASC 
             LIMIT ?"
        );
        $stmt->bindValue(1, '%' . $normalized . '%', \PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Product::class, [$this->logger]);
    }

    private function normalizeQuery(string $query): string {
        return preg_replace('/[^\p{L}\p{N}\s]/u', '', $query);
    }

    private function getSearchableFieldSql(string $column): string {
        $chars = ["'", "-", ".", ",", "!", "?", "(", ")", "/"];
        $sql = $column;
        foreach ($chars as $char) {
            $escapedChar = str_replace("'", "''", $char);
            $sql = "REPLACE($sql, '$escapedChar', '')";
        }
        return $sql;
    }

    private function getSortSql(string $sort): string {
        return match($sort) {
            'price_asc'  => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'featured'   => 'p.featured DESC, p.created_at DESC',
            default      => 'p.name',
        };
    }

    private function applyFilters(string &$sql, array &$params, array $filters): void {
        if (!empty($filters['product_ids']) && is_array($filters['product_ids'])) {
            $ids = array_map('intval', $filters['product_ids']);
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $sql .= " AND p.id IN ($placeholders)";
                foreach ($ids as $id) {
                    $params[] = $id;
                }
            }
        }

        if (!empty($filters['price_min'])) {
            $sql .= " AND p.price >= ?";
            $params[] = (float)$filters['price_min'];
        }
        if (!empty($filters['price_max'])) {
            $sql .= " AND p.price <= ?";
            $params[] = (float)$filters['price_max'];
        }
        
        if (!empty($filters['attributes']) && is_array($filters['attributes'])) {
            $placeholders = implode(',', array_fill(0, count($filters['attributes']), '?'));
            
            $stmt = $this->db->prepare("SELECT COUNT(DISTINCT attribute_id) FROM attribute_values WHERE id IN ($placeholders)");
            $stmt->execute($filters['attributes']);
            $expectedGroups = (int)$stmt->fetchColumn();

            if ($expectedGroups > 0) {
                $expectedGroupsInt = (int)$expectedGroups;
                $sql .= " AND p.id IN (
                    SELECT pav2.product_id 
                    FROM product_attribute_values pav2
                    JOIN attribute_values av2 ON pav2.attribute_value_id = av2.id
                    JOIN products p2 ON pav2.product_id = p2.id
                    LEFT JOIN product_variant_attributes pva ON p2.id = pva.product_id AND av2.attribute_id = pva.attribute_id
                    LEFT JOIN product_variants pv2 ON p2.id = pv2.product_id AND pv2.active = 1
                    LEFT JOIN product_variant_attribute_values pvav2 ON pv2.id = pvav2.variant_id AND av2.id = pvav2.attribute_value_id
                    WHERE pav2.attribute_value_id IN ($placeholders)
                      AND (p2.force_variant = 0 OR pva.attribute_id IS NULL OR pvav2.attribute_value_id IS NOT NULL)
                    GROUP BY pav2.product_id
                    HAVING COUNT(DISTINCT av2.attribute_id) = $expectedGroupsInt
                )";
                foreach ($filters['attributes'] as $attrId) {
                    $params[] = $attrId;
                }
            }
        }
    }
}
