<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\SecurityServiceInterface;
use App\Services\EmailServiceInterface;
use App\Services\OrderServiceInterface;
use App\Services\ReturnServiceInterface;
use App\Services\AuthServiceInterface;

class AdminOrdersController {
    public function __construct(
        private OrderServiceInterface $orderService,
        private ReturnServiceInterface $returnService,
        private AuthServiceInterface $auth,
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

        $history = $this->orderService->getStatusHistory($order_id);
        $returns = $this->returnService->getForOrder($order_id);

        $this->renderer->adminRender('orders_detail', [
            'page_title'  => 'Order ' . $order->getFormattedId(),
            'active'      => 'orders',
            'order'       => $order,
            'order_items' => $order->items,
            'history'     => $history,
            'returns'     => $returns,
            'flash_msg'   => flash('msg'),
            'flash_error' => flash('msg_error'),
        ]);
    }

    public function updateStatus() {
        $order_id = (int)($_POST['id'] ?? 0);
        $status   = $_POST['status'] ?? '';
        $notes    = trim($_POST['notes'] ?? '');
        $user     = $this->auth->currentUser();

        $allowed = [
            \App\Models\Order::STATUS_PENDING,
            \App\Models\Order::STATUS_CONFIRMED,
            \App\Models\Order::STATUS_SHIPPED,
            \App\Models\Order::STATUS_DELIVERED,
            \App\Models\Order::STATUS_CANCELLED,
            \App\Models\Order::STATUS_RETURNING,
            \App\Models\Order::STATUS_NOT_REFUNDED,
            \App\Models\Order::STATUS_FULLY_REFUNDED,
            \App\Models\Order::STATUS_PARTIAL_REFUND
        ];

        if ($order_id && in_array($status, $allowed)) {
            if ($status === \App\Models\Order::STATUS_CANCELLED) {
                if ($this->orderService->cancelOrder($order_id, $notes, $user?->id)) {
                    flash('msg', 'Order cancelled and stock replenished.');
                } else {
                    flash('msg_error', 'Order could not be cancelled.');
                }
            } else {
                $this->orderService->updateStatus($order_id, $status, $user?->id, $notes);

                $this->logger->info("Admin {admin_email} updated order {id} status to {status}", [
                    'admin_email' => $user?->email,
                    'id' => $order_id,
                    'status' => $status
                ]);

                if ($status === \App\Models\Order::STATUS_SHIPPED) {
                    $order = $this->orderService->findById($order_id);
                    if ($order && $order->customer_email) {
                        $this->email->sendStatusUpdateEmail($order->customer_email, $order_id, $status);
                    }
                }

                flash('msg', 'Order status updated.');
            }
        }

        redirect('/admin/orders/detail?id=' . $order_id);
    }
}
