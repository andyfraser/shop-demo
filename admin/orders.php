<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/render.php';

require_admin();

$db         = db();
$order_id   = (int)($_GET['id'] ?? 0);

// ── Update status ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $allowed = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
    $status  = in_array($_POST['status'], $allowed) ? $_POST['status'] : 'pending';
    $db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $order_id]);
    flash('msg', 'Order status updated.');
    redirect('orders.php?id=' . $order_id);
}

// ── Single order view ─────────────────────────────────────────────────────────
if ($order_id) {
    $stmt = $db->prepare(
        "SELECT o.*, u.name as user_name, u.email as user_email
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         WHERE o.id = ?"
    );
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        exit('Order not found.');
    }

    $stmt = $db->prepare(
        "SELECT oi.*, p.name as product_name, p.slug
         FROM order_items oi
         LEFT JOIN products p ON oi.product_id = p.id
         WHERE oi.order_id = ?"
    );
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();

    admin_render('orders_detail', [
        'page_title'  => 'Order #' . str_pad($order_id, 6, '0', STR_PAD_LEFT),
        'active'      => 'orders',
        'order'       => $order,
        'order_items' => $order_items,
        'flash_msg'   => flash('msg'),
    ]);

// ── Orders list ───────────────────────────────────────────────────────────────
} else {
    $filter   = $_GET['status'] ?? '';
    $allowed  = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
    $where    = ($filter && in_array($filter, $allowed))
                ? "WHERE o.status = " . $db->quote($filter)
                : '';

    $orders = $db->query(
        "SELECT o.*, u.name as user_name
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         $where
         ORDER BY o.created_at DESC"
    )->fetchAll();

    admin_render('orders_list', [
        'page_title' => 'Orders',
        'active'     => 'orders',
        'orders'     => $orders,
        'filter'     => $filter,
    ]);
}
