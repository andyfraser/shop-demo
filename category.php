<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/render.php';

$slug = $_GET['slug'] ?? '';

$stmt = db()->prepare("SELECT * FROM categories WHERE slug = ?");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    http_response_code(404);
    exit('Category not found.');
}

// Collect all descendant category IDs (self + children recursively)
function get_descendant_ids(int $id): array {
    $ids = [$id];
    $stmt = db()->prepare("SELECT id FROM categories WHERE parent_id = ?");
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll() as $child) {
        $ids = array_merge($ids, get_descendant_ids($child['id']));
    }
    return $ids;
}

$ids     = get_descendant_ids($category['id']);
$in      = implode(',', array_map('intval', $ids));
$per     = 12;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $per;

$total   = (int)db()->query("SELECT COUNT(*) FROM products WHERE category_id IN ($in) AND active = 1")->fetchColumn();
$pages   = (int)ceil($total / $per);

$products = db()->query(
    "SELECT p.*, c.name as cat_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.category_id IN ($in) AND p.active = 1
     LIMIT $per OFFSET $offset"
)->fetchAll();

$stmt = db()->prepare("SELECT * FROM categories WHERE parent_id = ?");
$stmt->execute([$category['id']]);
$subcategories = $stmt->fetchAll();

$breadcrumb = get_breadcrumb($category['id']);

render('category', [
    'page_title'    => $category['name'],
    'category'      => $category,
    'products'      => $products,
    'subcategories' => $subcategories,
    'breadcrumb'    => $breadcrumb,
    'total_products' => $total,
    'current_page'  => $page,
    'total_pages'   => $pages,
]);
