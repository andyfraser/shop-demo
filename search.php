<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/render.php';

$query    = trim($_GET['q'] ?? '');
$products = [];

if ($query) {
    $stmt = db()->prepare(
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

render('search', [
    'page_title'   => $query ? 'Search: ' . $query : 'Search',
    'search_query' => $query,
    'query'        => $query,
    'products'     => $products,
]);
