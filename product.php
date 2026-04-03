<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/render.php';

$slug = $_GET['slug'] ?? '';

$stmt = db()->prepare(
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    verify_csrf();
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    cart_add($product['id'], $qty);
    flash('success', money($qty * $product['price']) . ' added to your cart.');
    redirect('product.php?slug=' . urlencode($slug));
}

$breadcrumb = $product['category_id'] ? get_breadcrumb($product['category_id']) : [];

$stmt = db()->prepare(
    "SELECT p.*, c.name as cat_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.category_id = ? AND p.id != ? AND p.active = 1
     LIMIT 4"
);
$stmt->execute([$product['category_id'], $product['id']]);
$related_products = $stmt->fetchAll();

render('product', [
    'page_title'      => $product['name'],
    'product'         => $product,
    'breadcrumb'      => $breadcrumb,
    'related_products' => $related_products,
    'flash_success'   => flash('success'),
]);
