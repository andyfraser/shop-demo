<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\AuthService;
use App\Services\SecurityService;
use App\Services\EmailService;
use App\Services\OrderService;
use App\Services\UserService;

class AccountController {
    public function __construct(
        private OrderService $orderService,
        private UserService $userService,
        private Renderer $renderer,
        private AuthService $auth,
        private SecurityService $security,
        private EmailService $email,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function show() {
        $user = $this->auth->currentUser();
        $orders = $this->orderService->getForUser($user->id);

        $this->renderer->render('account', [
            'page_title'      => 'My Account',
            'orders'          => $orders,
            'address_saved'   => flash('address_saved'),
            'msg'             => flash('msg'),
            'msg_error'       => flash('msg_error'),
        ]);
    }

    public function saveAddress() {
        $this->security->verifyCsrf();

        $user    = $this->auth->currentUser();
        $address = trim($_POST['address'] ?? '');

        $this->userService->updateAddress($user->id, $address);

        $this->logger->notice("User {email} updated their address", ['email' => $user->email]);

        // Refresh session object
        $user->address = $address;

        flash('address_saved', '1');
        redirect('/account');
    }

    public function cancelOrder() {
        $this->security->verifyCsrf();
        $user = $this->auth->currentUser();
        $order_id = (int)($_POST['id'] ?? 0);

        if ($order_id) {
            $order = $this->orderService->findById($order_id);

            if ($order && $order->user_id === $user->id && $order->canBeCancelled()) {
                $this->orderService->updateStatus($order_id, \App\Models\Order::STATUS_CANCELLED);
                $this->logger->info("User {email} cancelled order {id}", [
                    'email' => $user->email,
                    'id' => $order_id
                ]);
                $this->email->sendStatusUpdateEmail($order->customer_email, $order_id, \App\Models\Order::STATUS_CANCELLED);
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
        $order = $this->orderService->findById($order_id);

        if (!$order || $order->user_id !== $user->id) {
            redirect('/account');
        }

        $this->renderer->render('order_confirm', [
            'page_title'  => 'Order Details',
            'order'       => $order,
            'order_items' => $order->items,
        ]);
    }
}
