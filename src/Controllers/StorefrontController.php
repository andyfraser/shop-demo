<?php
namespace App\Controllers;

use App\Core\Renderer;

class StorefrontController {
    private \PDO $db;
    private Renderer $renderer;

    public function __construct(\PDO $db, Renderer $renderer) {
        $this->db = $db;
        $this->renderer = $renderer;
    }

    public function index() {
        $featured_products = $this->db->query(
            "SELECT p.*, c.name as cat_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.active = 1 AND p.stock > 0
             ORDER BY p.featured DESC, p.created_at DESC
             LIMIT 8"
        )->fetchAll();

        $this->renderer->render('home', [
            'page_title'       => 'Welcome',
            'featured_products' => $featured_products,
        ]);
    }

    public function search() {
        $query    = trim($_GET['q'] ?? '');
        $products = [];
        $total_products = 0;
        $total_pages    = 1;
        $current_page   = max(1, (int)($_GET['page'] ?? 1));

        $sort = in_array($_GET['sort'] ?? '', ['name', 'price_asc', 'price_desc', 'featured']) ? $_GET['sort'] : 'name';
        $per_page_raw = $_GET['per_page'] ?? '12';
        $per_page_param = $per_page_raw === 'all' ? 'all' : (in_array((int)$per_page_raw, [12, 24]) ? (string)(int)$per_page_raw : '12');
        $per_page = $per_page_param === 'all' ? null : (int)$per_page_param;

        $order_by = match($sort) {
            'price_asc'  => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'featured'   => 'p.featured DESC, p.created_at DESC',
            default      => 'p.name',
        };

        if ($query) {
            $like = '%' . $query . '%';

            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM products p
                 WHERE p.active = 1 AND (p.name LIKE ? OR p.description LIKE ?)"
            );
            $stmt->execute([$like, $like]);
            $total_products = (int)$stmt->fetchColumn();

            if ($per_page !== null) {
                $total_pages = (int)ceil($total_products / $per_page);
                $offset = ($current_page - 1) * $per_page;
                $limit_int  = (int)$per_page;
                $offset_int = (int)$offset;
                $stmt = $this->db->prepare(
                    "SELECT p.*, c.name as cat_name
                     FROM products p
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE p.active = 1 AND (p.name LIKE ? OR p.description LIKE ?)
                     ORDER BY $order_by
                     LIMIT $limit_int OFFSET $offset_int"
                );
                $stmt->execute([$like, $like]);
            } else {
                $stmt = $this->db->prepare(
                    "SELECT p.*, c.name as cat_name
                     FROM products p
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE p.active = 1 AND (p.name LIKE ? OR p.description LIKE ?)
                     ORDER BY $order_by"
                );
                $stmt->execute([$like, $like]);
            }
            $products = $stmt->fetchAll();
        }

        $this->renderer->render('search', [
            'page_title'     => $query ? 'Search: ' . $query : 'Search',
            'search_query'   => $query,
            'query'          => $query,
            'products'       => $products,
            'total_products' => $total_products,
            'total_pages'    => $total_pages,
            'current_page'   => $current_page,
            'sort'           => $sort,
            'per_page_param' => $per_page_param,
        ]);
    }

    public function category($slug = '') {
        
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        $category = $stmt->fetch();

        if (!$category) {
            http_response_code(404);
            exit('Category not found.');
        }

        $stmt = $this->db->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY name");
        $stmt->execute([$category['id']]);
        $subcategories = $stmt->fetchAll();

        // Collect all descendant category IDs to include products from subcategories
        $cat_ids = [$category['id']];
        $queue = array_column($subcategories, 'id');
        while ($queue) {
            $id = array_shift($queue);
            $cat_ids[] = $id;
            $s = $this->db->prepare("SELECT id FROM categories WHERE parent_id = ?");
            $s->execute([$id]);
            foreach ($s->fetchAll() as $row) $queue[] = $row['id'];
        }
        $placeholders = implode(',', array_fill(0, count($cat_ids), '?'));

        $sort = in_array($_GET['sort'] ?? '', ['name', 'price_asc', 'price_desc', 'featured']) ? $_GET['sort'] : 'name';
        $per_page_raw = $_GET['per_page'] ?? '12';
        $per_page_param = $per_page_raw === 'all' ? 'all' : (in_array((int)$per_page_raw, [12, 24]) ? (string)(int)$per_page_raw : '12');
        $per_page = $per_page_param === 'all' ? null : (int)$per_page_param;

        $order_by = match($sort) {
            'price_asc'  => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'featured'   => 'p.featured DESC, p.created_at DESC',
            default      => 'p.name',
        };

        $current_page = max(1, (int)($_GET['page'] ?? 1));

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM products WHERE category_id IN ($placeholders) AND active = 1"
        );
        $stmt->execute($cat_ids);
        $total_products = (int)$stmt->fetchColumn();

        if ($per_page !== null) {
            $total_pages = (int)ceil($total_products / $per_page);
            $offset = ($current_page - 1) * $per_page;
            $limit_int  = (int)$per_page;
            $offset_int = (int)$offset;
            $stmt = $this->db->prepare(
                "SELECT p.*, c.name as cat_name
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.category_id IN ($placeholders) AND p.active = 1
                 ORDER BY $order_by
                 LIMIT $limit_int OFFSET $offset_int"
            );
            $stmt->execute($cat_ids);
        } else {
            $total_pages = 1;
            $stmt = $this->db->prepare(
                "SELECT p.*, c.name as cat_name
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.category_id IN ($placeholders) AND p.active = 1
                 ORDER BY $order_by"
            );
            $stmt->execute($cat_ids);
        }
        $products = $stmt->fetchAll();

        $breadcrumb = get_breadcrumb($category['id']);

        $this->renderer->render('category', [
            'page_title'     => $category['name'],
            'category'       => $category,
            'subcategories'  => $subcategories,
            'products'       => $products,
            'breadcrumb'     => $breadcrumb,
            'total_products' => $total_products,
            'total_pages'    => $total_pages,
            'current_page'   => $current_page,
            'sort'           => $sort,
            'per_page_param' => $per_page_param,
        ]);
    }

    public function products() {
        

        $sort = in_array($_GET['sort'] ?? '', ['name', 'price_asc', 'price_desc', 'featured']) ? $_GET['sort'] : 'name';
        $per_page_raw = $_GET['per_page'] ?? '12';
        $per_page_param = $per_page_raw === 'all' ? 'all' : (in_array((int)$per_page_raw, [12, 24]) ? (string)(int)$per_page_raw : '12');
        $per_page = $per_page_param === 'all' ? null : (int)$per_page_param;

        $order_by = match($sort) {
            'price_asc'  => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'featured'   => 'p.featured DESC, p.created_at DESC',
            default      => 'p.name',
        };

        $current_page = max(1, (int)($_GET['page'] ?? 1));

        $total_products = (int)$this->db->query("SELECT COUNT(*) FROM products WHERE active = 1")->fetchColumn();

        if ($per_page !== null) {
            $total_pages = (int)ceil($total_products / $per_page);
            $offset = ($current_page - 1) * $per_page;
            $limit_int  = (int)$per_page;
            $offset_int = (int)$offset;
            $stmt = $this->db->prepare(
                "SELECT p.*, c.name as cat_name
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.active = 1
                 ORDER BY $order_by
                 LIMIT $limit_int OFFSET $offset_int"
            );
            $stmt->execute([]);
        } else {
            $total_pages = 1;
            $stmt = $this->db->query(
                "SELECT p.*, c.name as cat_name
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.active = 1
                 ORDER BY $order_by"
            );
        }
        $products = $stmt->fetchAll();

        $this->renderer->render('products', [
            'page_title'     => 'All Products',
            'products'       => $products,
            'total_products' => $total_products,
            'total_pages'    => $total_pages,
            'current_page'   => $current_page,
            'sort'           => $sort,
            'per_page_param' => $per_page_param,
        ]);
    }

    public function product($slug = '') {
        
        $stmt = $this->db->prepare(
            "SELECT p.*, c.name as cat_name, c.slug as cat_slug
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.slug = ? AND p.active = 1"
        );
        $stmt->execute([$slug]);
        $product = $stmt->fetch();

        if (!$product) {
            http_response_code(404);
            exit('Product not found.');
        }

        $breadcrumb = $product['category_id'] ? get_breadcrumb($product['category_id']) : [];

        $stmt = $this->db->prepare(
            "SELECT p.*, c.name as cat_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.category_id = ? AND p.id != ? AND p.active = 1
             ORDER BY p.featured DESC, p.created_at DESC
             LIMIT 4"
        );
        $stmt->execute([$product['category_id'], $product['id']]);
        $related_products = $stmt->fetchAll();

        $this->renderer->render('product', [
            'page_title'      => $product['name'],
            'product'         => $product,
            'breadcrumb'      => $breadcrumb,
            'related_products' => $related_products,
            'flash_success'   => flash('success'),
        ]);
    }

    public function handleIcon() {
        header('Location: ' . BASE_URL . '/public/images/favicon.svg');
        exit;
    }
}
