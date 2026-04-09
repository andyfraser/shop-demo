<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Renderer;
use App\Core\Validator;
use App\Services\CartService;
use App\Services\AuthService;
use App\Services\SecurityService;
use App\Services\DeliveryService;
use App\Services\EmailService;

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
            'name'       => $user['name'] ?? '',
            'email'      => $user['email'] ?? '',
            'address'    => $user['address'] ?? '',
            'notes'      => '',
            'delivery_options' => DeliveryService::active(CartService::total()),
            'delivery_id' => null,
            'is_guest'   => $user === null,
        ]);
    }

    public function process() {
        $items = CartService::items();
        if (empty($items)) {
            redirect('/cart');
        }

        SecurityService::verifyCsrf();

        $name       = trim($_POST['name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $address    = trim($_POST['address'] ?? '');
        $notes      = trim($_POST['notes'] ?? '');
        $deliveryId = (int)($_POST['delivery_option_id'] ?? 0);

        $rules = [
            'name'               => 'required',
            'email'              => 'required|email',
            'address'            => 'required',
            'delivery_option_id' => 'required',
        ];

        $errors = Validator::check($_POST, $rules);

        $delivery = DeliveryService::get($deliveryId);
        if (!$delivery || !$delivery['active']) {
            $errors['delivery_option_id'] = 'Please select a valid delivery method.';
        }

        if (!$errors) {
            $user  = AuthService::currentUser();
            $total = CartService::total() + $delivery['price'];
            $db    = Database::getConnection();

            $db->prepare(
                "INSERT INTO orders (user_id, customer_name, customer_email, total, shipping_address, notes, status, delivery_method, delivery_cost)
                 VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?)"
            )->execute([
                $user['id'] ?? null,
                $name,
                $email,
                $total,
                $address,
                $notes,
                $delivery['name'],
                $delivery['price']
            ]);

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
            
            // Allow guest to see confirmation
            AuthService::sessionStart();
            $_SESSION['last_order_id'] = (int)$order_id;
            
            redirect('/order/confirm?id=' . $order_id);
        }

        Renderer::render('checkout', [
            'page_title' => 'Checkout',
            'items'      => $items,
            'total'      => CartService::total(),
            'errors'     => $errors,
            'name'       => $name,
            'email'      => $email,
            'address'    => $address,
            'notes'      => $notes,
            'delivery_options' => DeliveryService::active(CartService::total()),
            'delivery_id' => $deliveryId,
            'is_guest'   => AuthService::currentUser() === null,
        ]);
    }

    public function confirm() {
        $db       = Database::getConnection();
        $order_id = (int)($_GET['id'] ?? 0);

        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        $user = AuthService::currentUser();
        AuthService::sessionStart();
        $is_owner = ($user && $order && $order['user_id'] === $user['id']) || 
                    (isset($_SESSION['last_order_id']) && $_SESSION['last_order_id'] === $order_id);

        if (!$order || !$is_owner) {
            redirect('/');
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
'       => $order,
            'order_items' => $order_items,
        ]);
    }
}
cts p ON oi.product_id = p.id
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
