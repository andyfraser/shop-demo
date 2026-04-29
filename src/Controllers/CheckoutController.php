<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Core\Validator;
use App\Services\CartServiceInterface;
use App\Services\AuthServiceInterface;
use App\Services\SecurityServiceInterface;
use App\Services\DeliveryServiceInterface;
use App\Services\EmailServiceInterface;
use App\Services\OrderServiceInterface;
use App\Services\SettingsServiceInterface;
use App\Services\AddressServiceInterface;
use App\Services\Payment\PaymentServiceInterface;

class CheckoutController {
    public function __construct(
        private OrderServiceInterface $orderService,
        private Renderer $renderer,
        private CartServiceInterface $cart,
        private AuthServiceInterface $auth,
        private SecurityServiceInterface $security,
        private DeliveryServiceInterface $delivery,
        private EmailServiceInterface $email,
        private SettingsServiceInterface $settings,
        private PaymentServiceInterface $payment,
        private AddressServiceInterface $addressService,
        private Validator $validator,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function show() {
        $items = $this->cart->items();
        if (empty($items)) {
            redirect('/cart');
        }

        $user = $this->auth->currentUser();
        if ($user && !$user->isVerified()) {
            redirect('/cart?msg=verify_required');
        }

        $addresses = [];
        $defaultAddress = null;
        if ($user) {
            $addresses = $this->addressService->getByUserId($user->id);
            foreach ($addresses as $addr) {
                if ($addr['is_default']) {
                    $defaultAddress = $addr;
                    break;
                }
            }
        }

        $this->renderer->render('checkout', [
            'page_title' => 'Checkout',
            'items'      => $items,
            'total'      => $this->cart->total(),
            'total_item_vat' => $this->cart->totalVat(),
            'errors'     => [],
            'name'       => $user->name ?? '',
            'email'      => $user->email ?? '',
            'address'    => $defaultAddress['address'] ?? ($user->address ?? ''),
            'city'       => $defaultAddress['city'] ?? '',
            'postcode'   => $defaultAddress['postcode'] ?? '',
            'country'    => $defaultAddress['country'] ?? '',
            'addresses'  => $addresses,
            'notes'      => '',
            'delivery_options' => $this->delivery->active($this->cart->total()),
            'delivery_id' => null,
            'is_guest'   => $user === null,
        ]);
    }

    public function process() {
        $items = $this->cart->items();
        if (empty($items)) {
            redirect('/cart');
        }

        $this->security->verifyCsrf();

        $name       = trim($_POST['name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $address    = trim($_POST['address'] ?? '');
        $city       = trim($_POST['city'] ?? '');
        $postcode   = trim($_POST['postcode'] ?? '');
        $country    = trim($_POST['country'] ?? '');
        $notes      = trim($_POST['notes'] ?? '');
        $deliveryId = (int)($_POST['delivery_option_id'] ?? 0);

        $rules = [
            'name'               => 'required',
            'email'              => 'required|email',
            'address'            => 'required',
            'city'               => 'required',
            'postcode'           => 'required',
            'country'            => 'required',
            'delivery_option_id' => 'required',
        ];

        $errors = $this->validator->check($_POST, $rules);

        $deliveryOption = $this->delivery->get($deliveryId);
        if (!$deliveryOption || !$deliveryOption->active) {
            $errors['delivery_option_id'] = 'Please select a valid delivery method.';
        }

        if (!$errors) {
            $user  = $this->auth->currentUser();
            $total = $this->cart->total() + $deliveryOption->price;
            
            $defaultVatRate = (float)$this->settings->get('default_vat_rate');
            $deliveryVat = $deliveryOption->price * ($defaultVatRate / (100 + $defaultVatRate));
            $totalVat = $this->cart->totalVat() + $deliveryVat;
            
            $fullAddress = $address . "\n" . $city . "\n" . $postcode . "\n" . $country;

            $orderData = [
                'user_id'          => $user->id ?? null,
                'customer_name'    => $name,
                'customer_email'   => $email,
                'total'            => $total,
                'total_vat_amount' => $totalVat,
                'shipping_address' => $fullAddress,
                'notes'            => $notes,
                'delivery_method'  => $deliveryOption->name,
                'delivery_cost'    => $deliveryOption->price
            ];

            try {
                $order_id = $this->orderService->create($orderData, $items);
                $order = $this->orderService->findById($order_id);

                // Process payment (currently using the default manual gateway)
                $paymentResult = $this->payment->process('manual', $order);
                
                if ($paymentResult->success) {
                    $this->orderService->updatePaymentInfo(
                        $order_id, 
                        'manual', 
                        $paymentResult->status, 
                        $paymentResult->transactionId
                    );
                    
                    // If payment is successful, we can also confirm the order
                    $this->orderService->updateStatus($order_id, \App\Models\Order::STATUS_CONFIRMED, $user?->id, 'Paid via ' . $paymentResult->transactionId);
                    
                    $this->logger->info("New order placed and paid: ID {id}, Total {total}, Email {email}", [
                        'id' => $order_id,
                        'total' => $total,
                        'email' => $email
                    ]);
                } else {
                    $this->logger->warning("Order created but payment failed: ID {id}, Reason: {reason}", [
                        'id' => $order_id,
                        'reason' => $paymentResult->message
                    ]);
                    // You might want to handle failed payment differently, 
                    // e.g., redirecting to a payment retry page.
                }

                $order = $this->orderService->findById($order_id);
                // Convert items to array structure for email service
                $emailItems = array_map(fn($i) => [
                    'name' => $i->product_name ?? $i->name,
                    'variant_name' => $i->variant_name,
                    'quantity' => $i->quantity,
                    'unit_price' => $i->unit_price,
                    'vat_rate' => $i->vat_rate,
                    'vat_amount' => $i->vat_amount
                ], $order->items);

                $this->email->sendOrderConfirmation($order, $emailItems);

                $this->cart->clear();
                
                $this->auth->sessionStart();
                $_SESSION['last_order_id'] = (int)$order_id;
                
                redirect('/order/confirm?id=' . $order_id);
            } catch (\Exception $e) {
                $errors[] = "An error occurred while processing your order. Please try again.";
                $this->logger->error("Order creation failed: " . $e->getMessage());
            }
        }

        $this->renderer->render('checkout', [
            'page_title' => 'Checkout',
            'items'      => $items,
            'total'      => $this->cart->total(),
            'total_item_vat' => $this->cart->totalVat(),
            'errors'     => $errors,
            'name'       => $name,
            'email'      => $email,
            'address'    => $address,
            'notes'      => $notes,
            'delivery_options' => $this->delivery->active($this->cart->total()),
            'delivery_id' => $deliveryId,
            'is_guest'   => $this->auth->currentUser() === null,
        ]);
    }

    public function confirm() {
        $order_id = (int)($_GET['id'] ?? 0);
        $order = $this->orderService->findById($order_id);

        $user = $this->auth->currentUser();
        $this->auth->sessionStart();
        $is_owner = ($user && $order && $order->user_id === $user->id) || 
                    (isset($_SESSION['last_order_id']) && $_SESSION['last_order_id'] === $order_id);

        if (!$order || !$is_owner) {
            redirect('/');
        }

        $this->renderer->render('order_confirm', [
            'page_title'  => 'Order Confirmed',
            'order'       => $order,
            'order_items' => $order->items,
        ]);
    }
}
