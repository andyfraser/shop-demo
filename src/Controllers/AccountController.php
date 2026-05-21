<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Renderer;
use App\Services\AuthServiceInterface;
use App\Services\SecurityServiceInterface;
use App\Services\EmailServiceInterface;
use App\Services\OrderServiceInterface;
use App\Services\UserServiceInterface;
use App\Services\AddressServiceInterface;
use App\Services\ReturnServiceInterface;
use App\Services\VirtualProductServiceInterface;

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
        private \Psr\Log\LoggerInterface $logger,
        private VirtualProductServiceInterface $virtualProductService
    ) {}

    public function show(Request $request): Response {
        $user = $this->auth->currentUser();
        $orders = $this->orderService->getForUser($user->id);
        $addresses = $this->addressService->getByUserId($user->id);

        return new HtmlResponse($this->renderer->render('account', [
            'page_title'      => 'My Account',
            'orders'          => $orders,
            'addresses'       => $addresses,
            'address_saved'   => flash('address_saved'),
            'msg'             => flash('msg'),
            'msg_error'       => flash('msg_error'),
        ]));
    }

    public function downloads(Request $request): Response {
        $user = $this->auth->currentUser();
        $downloads = $this->virtualProductService->getUserDownloads($user->id);
        $licenses = $this->virtualProductService->getUserLicenses($user->id);
        $tickets = $this->virtualProductService->getUserTickets($user->id);

        return new HtmlResponse($this->renderer->render('account_downloads', [
            'page_title' => 'My Digital Library',
            'user'       => $user,
            'downloads'  => $downloads,
            'licenses'   => $licenses,
            'tickets'    => $tickets,
            'msg'        => flash('msg'),
            'msg_error'  => flash('msg_error'),
        ]));
    }

    public function newAddress(Request $request): Response {
        $user = $this->auth->currentUser();
        $addresses = $this->addressService->getByUserId($user->id);

        return new HtmlResponse($this->renderer->render('account_address_form', [
            'page_title' => 'Add New Address',
            'address' => null,
            'is_new' => true,
            'is_first' => empty($addresses),
        ]));
    }

    public function editAddress(Request $request): Response {
        $id = (int)$request->getQuery('id', 0);
        $user = $this->auth->currentUser();
        $address = $this->addressService->findById($id);

        if (!$address || $address->user_id !== $user->id) {
            return new RedirectResponse('/account');
        }

        return new HtmlResponse($this->renderer->render('account_address_form', [
            'page_title' => 'Edit Address',
            'address' => $address,
            'is_new' => false,
        ]));
    }

    public function saveAddress(Request $request): Response {
        $user = $this->auth->currentUser();
        $id = (int)$request->getPost('id', 0);

        $data = [
            'label'    => trim($request->getPost('label', '')),
            'name'     => trim($request->getPost('name', '')),
            'address'  => trim($request->getPost('address', '')),
            'city'     => trim($request->getPost('city', '')),
            'postcode' => trim($request->getPost('postcode', '')),
            'country'  => trim($request->getPost('country', '')),
            'is_default' => $request->getPost('is_default') ? 1 : 0,
        ];

        if (empty($data['label']) || empty($data['name']) || empty($data['address'])) {
            flash('msg_error', 'Label, Name and Address are required.');
            return new RedirectResponse($id ? '/account/addresses/edit?id='.$id : '/account/addresses/new');
        }

        $this->addressService->save($user->id, $data, $id);
        flash('msg', 'Address saved.');
        return new RedirectResponse('/account');
    }

    public function deleteAddress(Request $request): Response {
        $user = $this->auth->currentUser();
        $id = (int)$request->getPost('id', 0);

        if ($this->addressService->delete($id, $user->id)) {
            flash('msg', 'Address deleted.');
        }
        return new RedirectResponse('/account');
    }

    public function setDefaultAddress(Request $request): Response {
        $user = $this->auth->currentUser();
        $id = (int)$request->getPost('id', 0);

        if ($this->addressService->setDefault($id, $user->id)) {
            flash('msg', 'Default address updated.');
        }
        return new RedirectResponse('/account');
    }

    public function cancelOrder(Request $request): Response {
        $user = $this->auth->currentUser();
        $order_id = (int)$request->getPost('id', 0);

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
        return new RedirectResponse('/account');
    }

    public function requestReturn(Request $request): Response {
        $user = $this->auth->currentUser();
        $order_id = (int)$request->getPost('order_id', 0);
        $reason = trim($request->getPost('reason', ''));
        $items = $request->getPost('items', []); // Array of order_item_id => quantity

        if ($order_id) {
            if (empty($items)) {
                flash('msg_error', 'Please select at least one item to return.');
                return new RedirectResponse('/account/orders/' . $order_id);
            }

            try {
                $this->returnService->createReturnRequest($order_id, $user->id, $items, $reason);
                flash('msg', 'Return request submitted successfully.');
            } catch (\Exception $e) {
                flash('msg_error', 'Failed to submit return request: ' . $e->getMessage());
            }
        } else {
            flash('msg_error', 'Invalid return request.');
        }

        return new RedirectResponse('/account/orders/' . $order_id);
    }

    public function orderDetail(Request $request, $id): Response {
        $order_id = (int)$id;
        $user = $this->auth->currentUser();
        $order = $this->orderService->findById($order_id);

        if (!$order || $order->user_id !== $user->id) {
            return new RedirectResponse('/account');
        }

        $returns = $this->returnService->getForOrder($order_id);

        return new HtmlResponse($this->renderer->render('order_confirm', [
            'page_title'  => 'Order Details',
            'order'       => $order,
            'order_items' => $order->items,
            'returns'     => $returns,
            'flash_msg'   => flash('msg'),
            'flash_error' => flash('msg_error'),
        ]));
    }
}
