<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Renderer;

class StorefrontController {
    public function index() {
        $db = Database::getConnection();
        $featured_products = $db->query(
            "SELECT p.*, c.name as cat_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.active = 1
             ORDER BY p.id
             LIMIT 8"
        )->fetchAll();

        Renderer::render('home', [
            'page_title'       => 'Welcome',
            'featured_products' => $featured_products,
        ]);
    }

    public function search() {
        $query    = trim($_GET['q'] ?? '');
        $products = [];

        if ($query) {
            $stmt = Database::getConnection()->prepare(
                "SELECT p.*, c.name as cat_name
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.active = 1 AND (p.name LIKE ? OR p.description LIKE ?)
                 ORDER BY p.name"
            );
            $like = '%' . $query . '%';
            $stmt->execute([$like, $like]);
            $products = $stmt->fetchAll();
        }

        Renderer::render('search', [
            'page_title'   => $query ? 'Search: ' . $query : 'Search',
            'search_query' => $query,
            'query'        => $query,
            'products'     => $products,
        ]);
    }

    public function category() {
        $slug = $_GET['slug'] ?? '';
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        $category = $stmt->fetch();

        if (!$category) {
            http_response_code(404);
            exit('Category not found.');
        }

        $stmt = $db->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY name");
        $stmt->execute([$category['id']]);
        $subcategories = $stmt->fetchAll();

        // Collect all descendant category IDs to include products from subcategories
        $cat_ids = [$category['id']];
        $queue = array_column($subcategories, 'id');
        while ($queue) {
            $id = array_shift($queue);
            $cat_ids[] = $id;
            $s = $db->prepare("SELECT id FROM categories WHERE parent_id = ?");
            $s->execute([$id]);
            foreach ($s->fetchAll() as $row) $queue[] = $row['id'];
        }
        $placeholders = implode(',', array_fill(0, count($cat_ids), '?'));

        $per_page = 12;
        $current_page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($current_page - 1) * $per_page;

        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM products WHERE category_id IN ($placeholders) AND active = 1"
        );
        $stmt->execute($cat_ids);
        $total_products = (int)$stmt->fetchColumn();
        $total_pages = (int)ceil($total_products / $per_page);

        $stmt = $db->prepare(
            "SELECT p.*, c.name as cat_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.category_id IN ($placeholders) AND p.active = 1
             ORDER BY p.name
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([...$cat_ids, $per_page, $offset]);
        $products = $stmt->fetchAll();

        $breadcrumb = get_breadcrumb($category['id']);

        Renderer::render('category', [
            'page_title'     => $category['name'],
            'category'       => $category,
            'subcategories'  => $subcategories,
            'products'       => $products,
            'breadcrumb'     => $breadcrumb,
            'total_products' => $total_products,
            'total_pages'    => $total_pages,
            'current_page'   => $current_page,
        ]);
    }

    public function product() {
        $slug = $_GET['slug'] ?? '';
        $db = Database::getConnection();
        $stmt = $db->prepare(
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

        $stmt = $db->prepare(
            "SELECT p.*, c.name as cat_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.category_id = ? AND p.id != ? AND p.active = 1
             LIMIT 4"
        );
        $stmt->execute([$product['category_id'], $product['id']]);
        $related_products = $stmt->fetchAll();

        Renderer::render('product', [
            'page_title'      => $product['name'],
            'product'         => $product,
            'breadcrumb'      => $breadcrumb,
            'related_products' => $related_products,
            'flash_success'   => flash('success'),
        ]);
    }
}
