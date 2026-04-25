<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Renderer;
use App\Services\SecurityService;
use App\Services\EmailService;

class AdminOrdersController {
    private \PDO $db;
    private Renderer $renderer;
    private SecurityService $security;
    private EmailService $email;

    public function __construct(\PDO $db, Renderer $renderer, SecurityService $security, EmailService $email) {
        $this->db = $db;
        $this->renderer = $renderer;
        $this->security = $security;
        $this->email = $email;
    }

    public function list() {
        $filter   = $_GET['status'] ?? '';
        $allowed  = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
        $where    = ($filter && in_array($filter, $allowed))
                    ? "WHERE o.status = " . $this->db->quote($filter)
                    : '';

        $orders = $this->db->query(
            "SELECT o.*, 
                    COALESCE(u.name, o.customer_name) as user_name,
                    COALESCE(u.email, o.customer_email) as user_email
             FROM orders o
             LEFT JOIN users u ON o.user_id = u.id
             $where
             ORDER BY o.created_at DESC"
        )->fetchAll();

        $this->renderer->adminRender('orders_list', [
            'page_title' => 'Orders',
            'active'     => 'orders',
            'orders'     => $orders,
            'filter'     => $filter,
        ]);
    }

    public function detail() {
        $order_id = (int)($_GET['id'] ?? 0);

        if (!$order_id) {
            redirect('/admin/orders');
        }

        $stmt = $this->db->prepare(
            "SELECT o.*, 
                    COALESCE(u.name, o.customer_name) as user_name,
                    COALESCE(u.email, o.customer_email) as user_email
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

        $stmt = $this->db->prepare(
            "SELECT oi.*, p.name as product_name, p.slug
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?"
        );
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();

        $this->renderer->adminRender('orders_detail', [
            'page_title'  => 'Order #' . str_pad((string)$order_id, 6, '0', STR_PAD_LEFT),
            'active'      => 'orders',
            'order'       => $order,
            'order_items' => $order_items,
            'flash_msg'   => flash('msg'),
        ]);
    }

    public function updateStatus() {
        $this->security->verifyCsrf();
        $order_id = (int)($_POST['id'] ?? 0);
        $allowed = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
        $status  = in_array($_POST['status'] ?? '', $allowed) ? $_POST['status'] : 'pending';
        
        if ($order_id) {
            $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $order_id]);
            
            if ($status === 'shipped' || $status === 'cancelled') {
                $stmt = $this->db->prepare("SELECT customer_email FROM orders WHERE id = ?");
                $stmt->execute([$order_id]);
                $order = $stmt->fetch();
                if ($order && $order['customer_email']) {
                    $this->email->sendStatusUpdateEmail($order['customer_email'], $order_id, $status);
                }
            }
            
            flash('msg', 'Order status updated.');
        }
        
        redirect('/admin/orders/detail?id=' . $order_id);
    }
}
