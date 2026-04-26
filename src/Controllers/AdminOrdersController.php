<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\SecurityServiceInterface;
use App\Services\EmailServiceInterface;
use App\Services\OrderServiceInterface;

class AdminOrdersController {
    public function __construct(
        private OrderServiceInterface $orderService,
        private Renderer $renderer,
        private SecurityServiceInterface $security,
        private EmailServiceInterface $email,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function list() {
        $filter = $_GET['status'] ?? '';
        $orders = $this->orderService->getAllForAdmin($filter);

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

        $order = $this->orderService->findById($order_id);

        if (!$order) {
            http_response_code(404);
            exit('Order not found.');
        }

        $this->renderer->adminRender('orders_detail', [
            'page_title'  => 'Order ' . $order->getFormattedId(),
            'active'      => 'orders',
            'order'       => $order,
            'order_items' => $order->items,
            'flash_msg'   => flash('msg'),
        ]);
    }

    public function updateStatus() {
        $this->security->verifyCsrf();
        $order_id = (int)($_POST['id'] ?? 0);
        $allowed = [
            \App\Models\Order::STATUS_PENDING,
            \App\Models\Order::STATUS_CONFIRMED,
            \App\Models\Order::STATUS_SHIPPED,
            \App\Models\Order::STATUS_DELIVERED,
            \App\Models\Order::STATUS_CANCELLED
        ];
        $status  = in_array($_POST['status'] ?? '', $allowed) ? $_POST['status'] : \App\Models\Order::STATUS_PENDING;
        
        if ($order_id) {
            $this->orderService->updateStatus($order_id, $status);
            
            $this->logger->info("Admin updated order {id} status to {status}", [
                'id' => $order_id,
                'status' => $status
            ]);

            if ($status === \App\Models\Order::STATUS_SHIPPED || $status === \App\Models\Order::STATUS_CANCELLED) {
                $order = $this->orderService->findById($order_id);
                if ($order && $order->customer_email) {
                    $this->email->sendStatusUpdateEmail($order->customer_email, $order_id, $status);
                }
            }
            
            flash('msg', 'Order status updated.');
        }
        
        redirect('/admin/orders/detail?id=' . $order_id);
    }
}
