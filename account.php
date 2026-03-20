<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/render.php';

require_login();

$user = current_user();

$orders = db()->prepare(
    "SELECT o.*, COUNT(oi.id) as item_count
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     WHERE o.user_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC"
);
$orders->execute([$user['id']]);
$orders = $orders->fetchAll();

render('account', [
    'page_title' => 'My Account',
    'orders'     => $orders,
]);
