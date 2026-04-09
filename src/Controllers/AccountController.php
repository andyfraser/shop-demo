<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Renderer;
use App\Services\AuthService;
use App\Services\SecurityService;
use App\Services\EmailService;

class AccountController {
    public function show() {
        $user = AuthService::currentUser();

        $orders = Database::getConnection()->prepare(
            "SELECT o.*, COUNT(oi.id) as item_count
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE o.user_id = ?
             GROUP BY o.id
             ORDER BY o.created_at DESC"
        );
        $orders->execute([$user['id']]);

        Renderer::render('account', [
            'page_title'      => 'My Account',
            'orders'          => $orders->fetchAll(),
            'address_saved'   => flash('address_saved'),
            'msg'             => flash('msg'),
            'msg_error'       => flash('msg_error'),
        ]);
    }

    public function saveAddress() {
        SecurityService::verifyCsrf();

        $user    = AuthService::currentUser();
        $address = trim($_POST['address'] ?? '');

        Database::getConnection()->prepare(
            "UPDATE users SET address = ? WHERE id = ?"
        )->execute([$address, $user['id']]);

        // Refresh session so current_user() reflects the new address
        $_SESSION['user']['address'] = $address;

        flash('address_saved', '1');
        redirect('/account');
    }

    public function cancelOrder() {
        SecurityService::verifyCsrf();
        $user = AuthService::currentUser();
        $order_id = (int)($_POST['id'] ?? 0);

        if ($order_id) {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
            $stmt->execute([$order_id, $user['id']]);
            $order = $stmt->fetch();

            if ($order && $order['status'] === 'pending') {
                $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$order_id]);
                EmailService::getInstance()->sendStatusUpdateEmail($order['customer_email'], $order_id, 'cancelled');
                flash('msg', 'Order successfully cancelled.');
            } else {
                flash('msg_error', 'Order cannot be cancelled.');
            }
        }
        redirect('/account');
    }
}
