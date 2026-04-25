<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Renderer;
use App\Services\AuthService;
use App\Services\SecurityService;
use App\Services\EmailService;

class AccountController {
    public function __construct(
        private \PDO $db,
        private Renderer $renderer,
        private AuthService $auth,
        private SecurityService $security,
        private EmailService $email
    ) {}

    public function show() {
        $user = $this->auth->currentUser();

        $orders = $this->db->prepare(
            "SELECT o.*, COUNT(oi.id) as item_count
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE o.user_id = ?
             GROUP BY o.id
             ORDER BY o.created_at DESC"
        );
        $orders->execute([$user['id']]);

        $this->renderer->render('account', [
            'page_title'      => 'My Account',
            'orders'          => $orders->fetchAll(),
            'address_saved'   => flash('address_saved'),
            'msg'             => flash('msg'),
            'msg_error'       => flash('msg_error'),
        ]);
    }

    public function saveAddress() {
        $this->security->verifyCsrf();

        $user    = $this->auth->currentUser();
        $address = trim($_POST['address'] ?? '');

        $this->db->prepare(
            "UPDATE users SET address = ? WHERE id = ?"
        )->execute([$address, $user['id']]);

        // Refresh session so current_user() reflects the new address
        $_SESSION['user']['address'] = $address;

        flash('address_saved', '1');
        redirect('/account');
    }

    public function cancelOrder() {
        $this->security->verifyCsrf();
        $user = $this->auth->currentUser();
        $order_id = (int)($_POST['id'] ?? 0);

        if ($order_id) {
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
            $stmt->execute([$order_id, $user['id']]);
            $order = $stmt->fetch();

            if ($order && $order['status'] === 'pending') {
                $this->db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$order_id]);
                $this->email->sendStatusUpdateEmail($order['customer_email'], $order_id, 'cancelled');
                flash('msg', 'Order successfully cancelled.');
            } else {
                flash('msg_error', 'Order cannot be cancelled.');
            }
        }
        redirect('/account');
    }

    public function orderDetail($id) {
        $order_id = (int)$id;
        $user = $this->auth->currentUser();

        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$order_id, $user['id']]);
        $order = $stmt->fetch();

        if (!$order) {
            redirect('/account');
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
            'page_title'  => 'Order Details',
            'order'       => $order,
            'order_items' => $order_items,
        ]);
    }
}
