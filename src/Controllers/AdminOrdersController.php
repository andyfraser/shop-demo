<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Renderer;
use App\Services\SecurityService;

class AdminOrdersController {
    public function list() {
        $db = Database::getConnection();
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

        Renderer::adminRender('orders_list', [
            'page_title' => 'Orders',
            'active'     => 'orders',
            'orders'     => $orders,
            'filter'     => $filter,
        ]);
    }

    public function detail() {
        $db = Database::getConnection();
        $order_id = (int)($_GET['id'] ?? 0);

        if (!$order_id) {
            redirect('/admin/orders');
        }

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

        Renderer::adminRender('orders_detail', [
            'page_title'  => 'Order #' . str_pad((string)$order_id, 6, '0', STR_PAD_LEFT),
            'active'      => 'orders',
            'order'       => $order,
            'order_items' => $order_items,
            'flash_msg'   => flash('msg'),
        ]);
    }

    public function updateStatus() {
        SecurityService::verifyCsrf();
        $db = Database::getConnection();
        $order_id = (int)($_POST['id'] ?? 0);
        $allowed = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
        $status  = in_array($_POST['status'] ?? '', $allowed) ? $_POST['status'] : 'pending';
        
        if ($order_id) {
            $db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $order_id]);
            flash('msg', 'Order status updated.');
        }
        
        redirect('/admin/orders/detail?id=' . $order_id);
    }
}
