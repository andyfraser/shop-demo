<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/render.php';

require_admin();

$db = db();

$stats = [
    'products'  => $db->query("SELECT COUNT(*) FROM products WHERE active = 1")->fetchColumn(),
    'customers' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
    'orders'    => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'revenue'   => $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn(),
];

$recent_orders = $db->query(
    "SELECT o.*, u.name as user_name
     FROM orders o
     LEFT JOIN users u ON o.user_id = u.id
     ORDER BY o.created_at DESC
     LIMIT 8"
)->fetchAll();

$low_stock = $db->query(
    "SELECT * FROM products WHERE stock <= 5 AND active = 1 ORDER BY stock ASC LIMIT 5"
)->fetchAll();

admin_render('dashboard', [
    'page_title'    => 'Dashboard',
    'active'        => 'dashboard',
    'stats'         => $stats,
    'recent_orders' => $recent_orders,
    'low_stock'     => $low_stock,
]);
