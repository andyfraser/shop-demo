<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
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

    public function list(Request $request): Response {
        $criteria = \App\Core\QueryCriteria::fromRequest($request->getQuery());
        $orders = $this->orderService->find($criteria);

        return new HtmlResponse($this->renderer->adminRender('orders_list', [
            'page_title' => 'Orders',
            'active'     => 'orders',
            'orders'     => $orders,
            'filter'     => $criteria->getFilter('status', ''),
        ]));
    }

    public function detail(Request $request): Response {
        $order_id = (int)$request->getQuery('id', 0);

        if (!$order_id) {
            return new RedirectResponse('/admin/orders');
        }

        $order = $this->orderService->findById($order_id);

        if (!$order) {
            return new HtmlResponse('Order not found.', 404);
        }

        $history = $this->orderService->getStatusHistory($order_id);
        $returns = $this->returnService->getForOrder($order_id);

        return new HtmlResponse($this->renderer->adminRender('orders_detail', [
            'page_title'  => 'Order ' . $order->getFormattedId(),
            'active'      => 'orders',
            'order'       => $order,
            'order_items' => $order->items,
            'history'     => $history,
            'returns'     => $returns,
            'flash_msg'   => flash('msg'),
            'flash_error' => flash('msg_error'),
        ]));
    }

    public function updateStatus(Request $request): Response {
        $post = $request->getPost();
        $order_id = (int)($post['id'] ?? 0);
        $status   = $post['status'] ?? '';
        $notes    = trim($post['notes'] ?? '');
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

                flash('msg', 'Order status updated.');
            }
        }

        return new RedirectResponse('/admin/orders/detail?id=' . $order_id);
    }
}
