<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/render.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['update'])) {
        foreach (($_POST['qty'] ?? []) as $id => $qty) {
            cart_update((int)$id, (int)$qty);
        }
        flash('success', 'Cart updated.');
    }
    if (isset($_POST['remove'])) {
        cart_remove((int)$_POST['remove']);
    }
    redirect('cart.php');
}

$items = cart_items();
$total = cart_total();

render('cart', [
    'page_title'    => 'Shopping Cart',
    'items'         => $items,
    'total'         => $total,
    'flash_success' => flash('success'),
]);
