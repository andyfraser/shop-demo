<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/render.php';

$featured_products = db()->query(
    "SELECT p.*, c.name as cat_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.active = 1
     ORDER BY p.id
     LIMIT 8"
)->fetchAll();

render('home', [
    'page_title'       => 'Welcome',
    'featured_products' => $featured_products,
]);
