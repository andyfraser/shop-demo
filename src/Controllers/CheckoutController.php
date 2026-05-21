<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Responses\JsonResponse;
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
use App\Services\PricingServiceInterface;
use App\Services\VirtualProductServiceInterface;
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
        private PricingServiceInterface $pricingService,
        private Validator $validator,
        private VirtualProductServiceInterface $virtualProductService,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function show(Request $request): Response {
        $items = $this->cart->items();
        if (empty($items)) {
            return new RedirectResponse('/cart');
        }

        $user = $this->auth->currentUser();
        $userRole = $user?->role ?? null;
        $addresses = [];
        $defaultAddress = null;
        if ($user) {
            $addresses = $this->addressService->getByUserId($user->id);
            foreach ($addresses as $addr) {
                if ($addr->isDefault()) {
                    $defaultAddress = $addr;
                    break;
                }
            }
        }

        return new HtmlResponse($this->renderer->render('checkout', [
            'page_title' => 'Checkout',
            'cart'       => $this->cart,
            'items'      => $items,
            'total'      => $this->cart->total(),
            'total_item_vat' => $this->cart->totalVat(),
            'discount'   => $this->cart->discount(),
            'grand_total' => $this->cart->grandTotal(),
            'applied_promotions' => $this->cart->getAppliedPromotions(),
            'errors'     => [],
            'name'       => $user->name ?? '',
            'email'      => $user->email ?? '',
            'address'    => $defaultAddress?->address ?? ($user->address ?? ''),
            'city'       => $defaultAddress?->city ?? '',
            'postcode'   => $defaultAddress?->postcode ?? '',
            'country'    => $defaultAddress?->country ?? '',
            'addresses'  => $addresses,
            'notes'      => '',
            'delivery_options' => $this->cart->isVirtualOnly() ? [] : $this->delivery->active($this->cart->total(), $userRole),
            'delivery_id' => null,
            'is_guest'   => $user === null,
            'is_virtual_only' => $this->cart->isVirtualOnly(),
            'gift_card_code' => '',
            'gift_card_discount' => 0.0,
        ]));
    }

    public function process(Request $request): Response {
        $items = $this->cart->items();
        if (empty($items)) {
            return new RedirectResponse('/cart');
        }

        $post = $request->getPost();
        $name       = trim($post['name'] ?? '');
        $email      = trim($post['email'] ?? '');
        $address    = trim($post['address'] ?? '');
        $city       = trim($post['city'] ?? '');
        $postcode   = trim($post['postcode'] ?? '');
        $country    = trim($post['country'] ?? '');
        $notes      = trim($post['notes'] ?? '');
        $deliveryId = (int)($post['delivery_option_id'] ?? 0);
        $giftCardCode = trim($post['gift_card_code'] ?? '');

        $isVirtualOnly = $this->cart->isVirtualOnly();

        if ($isVirtualOnly) {
            $rules = [
                'name'  => 'required',
                'email' => 'required|email',
            ];
        } else {
            $rules = [
                'name'               => 'required',
                'email'              => 'required|email',
                'address'            => 'required',
                'city'               => 'required',
                'postcode'           => 'required',
                'country'            => 'required',
                'delivery_option_id' => 'required',
            ];
        }

        $errors = $this->validator->check($post, $rules);

        $deliveryCost = 0.0;
        $deliveryMethod = 'Digital Delivery';
        $fullAddress = 'Digital Delivery';
        $deliveryOption = null;

        if (!$isVirtualOnly) {
            $deliveryOption = $this->delivery->get($deliveryId);
            if (!$deliveryOption || !$deliveryOption->active) {
                $errors['delivery_option_id'] = 'Please select a valid delivery method.';
            } else {
                $deliveryCost = $deliveryOption->price;
                $deliveryMethod = $deliveryOption->name;
                $fullAddress = $address . "\n" . $city . "\n" . $postcode . "\n" . $country;
            }
        }

        $giftCardDiscount = 0.0;

        if ($giftCardCode !== '' && !$errors) {
            $discount = $this->cart->discount();
            $defaultVatRate = (float)$this->settings->get('default_vat_rate');
            $totals = $this->pricingService->calculateOrderTotals(
                $this->cart->total(),
                $this->cart->totalVat(),
                $discount,
                $deliveryCost,
                $defaultVatRate
            );
            $prospectiveTotal = $totals['grand_total'];

            $gcResult = $this->virtualProductService->applyGiftCardCode($giftCardCode, $prospectiveTotal);
            if (!$gcResult['success']) {
                $errors['gift_card_code'] = $gcResult['message'];
            } else {
                $giftCardDiscount = $gcResult['discount'];
            }
        }

        if (!$errors) {
            $user  = $this->auth->currentUser();
            $discount = $this->cart->discount();
            $appliedPromos = $this->cart->getAppliedPromotions();
            
            $defaultVatRate = (float)$this->settings->get('default_vat_rate');
            
            $totals = $this->pricingService->calculateOrderTotals(
                $this->cart->total(),
                $this->cart->totalVat(),
                $discount,
                $deliveryCost,
                $defaultVatRate
            );
            
            $total = $totals['grand_total'] - $giftCardDiscount;
            $totalVat = $totals['total_vat'];

            // Map promotions for OrderService
            $orderPromos = array_map(fn($p) => [
                'promotion_id' => $p->id,
                'name' => $p->name,
                'discount_amount' => $this->cart->getPromotionDiscount($p),
                'promo_code' => $p->applied_code ?? $p->code
            ], $appliedPromos);

            $primaryPromo = !empty($appliedPromos) ? $appliedPromos[0] : null;

            $finalNotes = $notes;
            if ($giftCardCode !== '' && $giftCardDiscount > 0) {
                $giftCardNote = "[Gift Card Applied: Code = {$giftCardCode}, Discount = " . money($giftCardDiscount) . "]";
                $finalNotes = $finalNotes !== '' ? $giftCardNote . "\n" . $finalNotes : $giftCardNote;
            }

            $orderData = [
                'user_id'          => $user->id ?? null,
                'customer_name'    => $name,
                'customer_email'   => $email,
                'total'            => $total,
                'total_vat_amount' => $totalVat,
                'shipping_address' => $fullAddress,
                'notes'            => $finalNotes,
                'delivery_method'  => $deliveryMethod,
                'delivery_cost'    => $deliveryCost,
                'promotion_id'     => $primaryPromo?->id,
                'discount_amount'  => $discount,
                'gift_card_amount' => $giftCardDiscount,
                'applied_promo_name' => $primaryPromo?->name,
                'applied_promo_code' => $primaryPromo?->applied_code ?? $primaryPromo?->code,
                'applied_promotions' => $orderPromos
            ];

            try {
                $order_id = $this->orderService->create($orderData, $items);
                $order = $this->orderService->findById($order_id);

                if ($giftCardCode !== '' && $giftCardDiscount > 0) {
                    $this->virtualProductService->deductGiftCardBalance($giftCardCode, $giftCardDiscount);
                }

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
                }

                $order = $this->orderService->findById($order_id);

                $this->cart->clear();
                
                $this->auth->sessionStart();
                $_SESSION['last_order_id'] = (int)$order_id;
                
                return new RedirectResponse('/order/confirm?id=' . $order_id);
            } catch (\Exception $e) {
                $errors[] = "Order creation failed: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
                $this->logger->error("Order creation failed: " . $e->getMessage());
            }
        }

        $user = $this->auth->currentUser();
        $userRole = $user?->role ?? null;
        return new HtmlResponse($this->renderer->render('checkout', [
            'page_title' => 'Checkout',
            'cart'       => $this->cart,
            'items'      => $items,
            'total'      => $this->cart->total(),
            'total_item_vat' => $this->cart->totalVat(),
            'discount'   => $this->cart->discount(),
            'grand_total' => $this->cart->grandTotal() - $giftCardDiscount,
            'applied_promotions' => $this->cart->getAppliedPromotions(),
            'errors'     => $errors,
            'name'       => $name,
            'email'      => $email,
            'address'    => $address,
            'city'       => $city,
            'postcode'   => $postcode,
            'country'    => $country,
            'notes'      => $notes,
            'delivery_options' => $isVirtualOnly ? [] : $this->delivery->active($this->cart->total(), $userRole),
            'delivery_id' => $isVirtualOnly ? null : $deliveryId,
            'is_guest'   => $this->auth->currentUser() === null,
            'is_virtual_only' => $isVirtualOnly,
            'gift_card_code' => $giftCardCode,
            'gift_card_discount' => $giftCardDiscount,
        ]));
    }

    public function applyGiftCardAjax(Request $request): Response {
        $post = $request->getPost();
        $code = trim($post['gift_card_code'] ?? '');
        $prospectiveTotal = (float)($post['total'] ?? 0.0);

        $gcResult = $this->virtualProductService->applyGiftCardCode($code, $prospectiveTotal);
        if ($gcResult['success']) {
            return new JsonResponse([
                'success' => true,
                'discount' => $gcResult['discount'],
                'discount_formatted' => money($gcResult['discount']),
                'remaining' => $gcResult['remaining'],
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => $gcResult['message'],
        ]);
    }

    public function confirm(Request $request): Response {
        $order_id = (int)$request->getQuery('id', 0);
        $order = $this->orderService->findById($order_id);

        $user = $this->auth->currentUser();
        $this->auth->sessionStart();
        $is_owner = ($user && $order && $order->user_id === $user->id) || 
                    (isset($_SESSION['last_order_id']) && $_SESSION['last_order_id'] === $order_id);

        if (!$order || !$is_owner) {
            return new RedirectResponse('/');
        }

        return new HtmlResponse($this->renderer->render('order_confirm', [
            'page_title'  => 'Order Confirmed',
            'order'       => $order,
            'order_items' => $order->items,
        ]));
    }
}
