<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Renderer;
use App\Core\Validator;
use App\Services\CartService;
use App\Services\AuthService;
use App\Services\SecurityService;

class CheckoutController {
    public function show() {
        $items = CartService::items();
        if (empty($items)) {
            redirect('/cart');
        }

        Renderer::render('checkout', [
            'page_title' => 'Checkout',
            'items'      => $items,
            'total'      => CartService::total(),
            'errors'     => [],
            'address'    => '',
            'notes'      => '',
        ]);
    }

    public function process() {
        $items = CartService::items();
        if (empty($items)) {
            redirect('/cart');
        }

        SecurityService::verifyCsrf();

        $address = trim($_POST['address'] ?? '');
        $notes   = trim($_POST['notes'] ?? '');

        $errors = Validator::check($_POST, [
            'address' => 'required',
        ]);

        if (!$errors) {
            $user  = AuthService::currentUser();
            $total = CartService::total();
            $db    = Database::getConnection();

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

            CartService::clear();
            redirect('/order/confirm?id=' . $order_id);
        }

        Renderer::render('checkout', [
            'page_title' => 'Checkout',
            'items'      => $items,
            'total'      => CartService::total(),
            'errors'     => $errors,
            'address'    => $address,
            'notes'      => $notes,
        ]);
    }

    public function confirm() {
        $db       = Database::getConnection();
        $order_id = (int)($_GET['id'] ?? 0);

        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        if (!$order || $order['user_id'] !== AuthService::currentUser()['id']) {
            redirect('/account');
        }

        $stmt = $db->prepare(
            "SELECT oi.*, p.name, p.slug
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?"
        );
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();

        Renderer::render('order_confirm', [
            'page_title'  => 'Order Confirmed',
            'order'       => $order,
            'order_items' => $order_items,
        ]);
    }
}
