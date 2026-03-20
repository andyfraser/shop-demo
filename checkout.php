<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/render.php';

require_login();

$items = cart_items();
if (empty($items)) {
    redirect('cart.php');
}

$errors  = [];
$address = '';
$notes   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    $notes   = trim($_POST['notes'] ?? '');

    if (!$address) {
        $errors[] = 'Please enter a shipping address.';
    }

    if (!$errors) {
        $user  = current_user();
        $total = cart_total();
        $db    = db();

        $db->prepare(
            "INSERT INTO orders (user_id, total, shipping_address, notes, status)
             VALUES (?, ?, ?, ?, 'pending')"
        )->execute([$user['id'], $total, $address, $notes]);

        $order_id = $db->lastInsertId();

        $ins = $db->prepare(
            "INSERT INTO order_items (order_id, product_id, quantity, unit_price)
             VALUES (?, ?, ?, ?)"
        );
        foreach ($items as $item) {
            $ins->execute([$order_id, $item['id'], $item['qty'], $item['price']]);
            $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")
               ->execute([$item['qty'], $item['id']]);
        }

        cart_clear();
        redirect('order_confirm.php?id=' . $order_id);
    }
}

render('checkout', [
    'page_title' => 'Checkout',
    'items'      => $items,
    'total'      => cart_total(),
    'errors'     => $errors,
    'address'    => $address,
    'notes'      => $notes,
]);
