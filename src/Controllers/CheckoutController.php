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
use App\Services\SettingsService;

class CheckoutController {
    public function __construct(
        private \PDO $db,
        private Renderer $renderer,
        private CartService $cart,
        private AuthService $auth,
        private SecurityService $security,
        private DeliveryService $delivery,
        private EmailService $email,
        private SettingsService $settings,
        private Validator $validator
    ) {}

    public function show() {
        $items = $this->cart->items();
        if (empty($items)) {
            redirect('/cart');
        }

        $user = $this->auth->currentUser();
        if ($user && empty($user['is_verified'])) {
            redirect('/cart?msg=verify_required');
        }

        $this->renderer->render('checkout', [
            'page_title' => 'Checkout',
            'items'      => $items,
            'total'      => $this->cart->total(),
            'total_item_vat' => $this->cart->totalVat(),
            'errors'     => [],
            'name'       => $user['name'] ?? '',
            'email'      => $user['email'] ?? '',
            'address'    => $user['address'] ?? '',
            'notes'      => '',
            'delivery_options' => $this->delivery->active($this->cart->total()),
            'delivery_id' => null,
            'is_guest'   => $user === null,
        ]);
    }

    public function process() {
        $items = $this->cart->items();
        if (empty($items)) {
            redirect('/cart');
        }

        $this->security->verifyCsrf();

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

        $errors = $this->validator->check($_POST, $rules);

        $deliveryOption = $this->delivery->get($deliveryId);
        if (!$deliveryOption || !$deliveryOption['active']) {
            $errors['delivery_option_id'] = 'Please select a valid delivery method.';
        }

        if (!$errors) {
            $user  = $this->auth->currentUser();
            $total = $this->cart->total() + $deliveryOption['price'];
            
            $defaultVatRate = (float)$this->settings->get('default_vat_rate');
            $deliveryVat = $deliveryOption['price'] * ($defaultVatRate / (100 + $defaultVatRate));
            $totalVat = $this->cart->totalVat() + $deliveryVat;
            
            $this->db->prepare(
                "INSERT INTO orders (user_id, customer_name, customer_email, total, total_vat_amount, shipping_address, notes, status, delivery_method, delivery_cost)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)"
            )->execute([
                $user['id'] ?? null,
                $name,
                $email,
                $total,
                $totalVat,
                $address,
                $notes,
                $deliveryOption['name'],
                $deliveryOption['price']
            ]);

            $order_id = $this->db->lastInsertId();

            $ins = $this->db->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity, unit_price, vat_rate, vat_amount)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $orderItems = [];
            foreach ($items as $item) {
                $ins->execute([$order_id, $item['id'], $item['qty'], $item['price'], $item['vat_rate'], $item['vat_amount']]);
                $this->db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")
                   ->execute([$item['qty'], $item['id']]);
                $orderItems[] = [
                    'name' => $item['name'],
                    'quantity' => $item['qty'],
                    'unit_price' => $item['price'],
                    'vat_rate' => $item['vat_rate'],
                    'vat_amount' => $item['vat_amount']
                ];
            }

            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
            $this->email->sendOrderConfirmation($order, $orderItems);

            $this->cart->clear();
            
            // Allow guest to see confirmation
            $this->auth->sessionStart();
            $_SESSION['last_order_id'] = (int)$order_id;
            
            redirect('/order/confirm?id=' . $order_id);
        }

        $this->renderer->render('checkout', [
            'page_title' => 'Checkout',
            'items'      => $items,
            'total'      => $this->cart->total(),
            'total_item_vat' => $this->cart->totalVat(),
            'errors'     => $errors,
            'name'       => $name,
            'email'      => $email,
            'address'    => $address,
            'notes'      => $notes,
            'delivery_options' => $this->delivery->active($this->cart->total()),
            'delivery_id' => $deliveryId,
            'is_guest'   => $this->auth->currentUser() === null,
        ]);
    }

    public function confirm() {
        $order_id = (int)($_GET['id'] ?? 0);

        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        $user = $this->auth->currentUser();
        $this->auth->sessionStart();
        $is_owner = ($user && $order && $order['user_id'] === $user['id']) || 
                    (isset($_SESSION['last_order_id']) && $_SESSION['last_order_id'] === $order_id);

        if (!$order || !$is_owner) {
            redirect('/');
        }

        $stmt = $this->db->prepare(
            "SELECT oi.*, p.name, p.slug
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?"
        );
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();

        $this->renderer->render('order_confirm', [
            'page_title'  => 'Order Confirmed',
            'order'       => $order,
            'order_items' => $order_items,
        ]);
    }
}
