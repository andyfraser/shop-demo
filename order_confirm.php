<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/render.php';

require_login();

$user = current_user();

$stmt = db()->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([(int)($_GET['id'] ?? 0), $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect('account.php');
}

$stmt = db()->prepare(
    "SELECT oi.*, p.name, p.slug
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?"
);
$stmt->execute([$order['id']]);
$order_items = $stmt->fetchAll();

render('order_confirm', [
    'page_title'  => 'Order Confirmed',
    'order'       => $order,
    'order_items' => $order_items,
]);
