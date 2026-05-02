<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\AuthServiceInterface;
use App\Services\SecurityServiceInterface;
use App\Services\EmailServiceInterface;
use App\Services\OrderServiceInterface;
use App\Services\UserServiceInterface;
use App\Services\AddressServiceInterface;
use App\Services\ReturnServiceInterface;

class AccountController {
    public function __construct(
        private OrderServiceInterface $orderService,
        private UserServiceInterface $userService,
        private Renderer $renderer,
        private AuthServiceInterface $auth,
        private SecurityServiceInterface $security,
        private EmailServiceInterface $email,
        private ReturnServiceInterface $returnService,
        private AddressServiceInterface $addressService,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function show() {
        $user = $this->auth->currentUser();
        $orders = $this->orderService->getForUser($user->id);
        $addresses = $this->addressService->getByUserId($user->id);

        $this->renderer->render('account', [
            'page_title'      => 'My Account',
            'orders'          => $orders,
            'addresses'       => $addresses,
            'address_saved'   => flash('address_saved'),
            'msg'             => flash('msg'),
            'msg_error'       => flash('msg_error'),
        ]);
    }

    public function newAddress() {
        $user = $this->auth->currentUser();
        $addresses = $this->addressService->getByUserId($user->id);

        $this->renderer->render('account_address_form', [
            'page_title' => 'Add New Address',
            'address' => null,
            'is_new' => true,
            'is_first' => empty($addresses),
        ]);
    }

    public function editAddress() {
        $id = (int)($_GET['id'] ?? 0);
        $user = $this->auth->currentUser();
        $address = $this->addressService->findById($id);

        if (!$address || $address->user_id !== $user->id) {
            redirect('/account');
        }

        $this->renderer->render('account_address_form', [
            'page_title' => 'Edit Address',
            'address' => $address,
            'is_new' => false,
        ]);
    }

    public function saveAddress() {
        $user = $this->auth->currentUser();
        $id = (int)($_POST['id'] ?? 0);

        $data = [
            'label'    => trim($_POST['label'] ?? ''),
            'name'     => trim($_POST['name'] ?? ''),
            'address'  => trim($_POST['address'] ?? ''),
            'city'     => trim($_POST['city'] ?? ''),
            'postcode' => trim($_POST['postcode'] ?? ''),
            'country'  => trim($_POST['country'] ?? ''),
            'is_default' => isset($_POST['is_default']) ? 1 : 0,
        ];

        if (empty($data['label']) || empty($data['name']) || empty($data['address'])) {
            flash('msg_error', 'Label, Name and Address are required.');
            redirect($id ? '/account/addresses/edit?id='.$id : '/account/addresses/new');
        }

        $this->addressService->save($user->id, $data, $id);
        flash('msg', 'Address saved.');
        redirect('/account');
    }

    public function deleteAddress() {
        $user = $this->auth->currentUser();
        $id = (int)($_POST['id'] ?? 0);

        if ($this->addressService->delete($id, $user->id)) {
            flash('msg', 'Address deleted.');
        }
        redirect('/account');
    }

    public function setDefaultAddress() {
        $user = $this->auth->currentUser();
        $id = (int)($_POST['id'] ?? 0);

        if ($this->addressService->setDefault($id, $user->id)) {
            flash('msg', 'Default address updated.');
        }
        redirect('/account');
    }

    public function cancelOrder() {
        $user = $this->auth->currentUser();
        $order_id = (int)($_POST['id'] ?? 0);

        if ($order_id) {
            $order = $this->orderService->findById($order_id);
            if ($order && $order->user_id === $user->id) {
                if ($this->orderService->cancelOrder($order_id, '', $user->id)) {
                    $this->logger->info("User {email} cancelled order {id}", [
                        'email' => $user->email,
                        'id'    => $order_id
                    ]);
                    flash('msg', 'Order successfully cancelled.');
                } else {
                    flash('msg_error', 'Order cannot be cancelled.');
                }
            }
        }
        redirect('/account');
    }

    public function requestReturn() {
        $user = $this->auth->currentUser();
        $order_id = (int)($_POST['order_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $items = $_POST['items'] ?? []; // Array of order_item_id => quantity

        if ($order_id) {
            if (empty($items)) {
                flash('msg_error', 'Please select at least one item to return.');
                redirect('/account/orders/' . $order_id);
            }

            try {
                $this->returnService->createReturnRequest($order_id, $items, $reason);
                flash('msg', 'Return request submitted successfully.');
            } catch (\Exception $e) {
                flash('msg_error', 'Failed to submit return request: ' . $e->getMessage());
            }
        } else {
            flash('msg_error', 'Invalid return request.');
        }

        redirect('/account/orders/' . $order_id);
    }

    public function orderDetail($id) {
        $order_id = (int)$id;
        $user = $this->auth->currentUser();
        $order = $this->orderService->findById($order_id);

        if (!$order || $order->user_id !== $user->id) {
            redirect('/account');
        }

        $returns = $this->returnService->getForOrder($order_id);

        $this->renderer->render('order_confirm', [
            'page_title'  => 'Order Details',
            'order'       => $order,
            'order_items' => $order->items,
            'returns'     => $returns,
            'flash_msg'   => flash('msg'),
            'flash_error' => flash('msg_error'),
        ]);
    }
}
