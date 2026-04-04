<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Renderer;
use App\Core\Validator;
use App\Services\CartService;
use App\Services\AuthService;
use App\Services\SecurityService;
use App\Services\DeliveryService;

class CheckoutController {
    public function show() {
        $items = CartService::items();
        if (empty($items)) {
            redirect('/cart');
        }

        $user = AuthService::currentUser();

        Renderer::render('checkout', [
            'page_title' => 'Checkout',
            'items'      => $items,
            'total'      => CartService::total(),
            'errors'     => [],
            'address'    => $user['address'] ?? '',
            'notes'      => '',
            'delivery_options' => DeliveryService::active(CartService::total()),
            'delivery_id' => null,
        ]);
    }

    public function process() {
        $items = CartService::items();
        if (empty($items)) {
            redirect('/cart');
        }

        SecurityService::verifyCsrf();

        $address    = trim($_POST['address'] ?? '');
        $notes      = trim($_POST['notes'] ?? '');
        $deliveryId = (int)($_POST['delivery_option_id'] ?? 0);

        $errors = Validator::check($_POST, [
            'address'            => 'required',
            'delivery_option_id' => 'required',
        ]);

        $delivery = DeliveryService::get($deliveryId);
        if (!$delivery || !$delivery['active']) {
            $errors['delivery_option_id'] = 'Please select a valid delivery method.';
        }

        if (!$errors) {
            $user  = AuthService::currentUser();
            $total = CartService::total() + $delivery['price'];
            $db    = Database::getConnection();

            $db->prepare(
                "INSERT INTO orders (user_id, total, shipping_address, notes, status, delivery_method, delivery_cost)
                 VALUES (?, ?, ?, ?, 'pending', ?, ?)"
            )->execute([$user['id'], $total, $address, $notes, $delivery['name'], $delivery['price']]);

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
            'delivery_options' => DeliveryService::active(CartService::total()),
            'delivery_id' => $deliveryId,
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
