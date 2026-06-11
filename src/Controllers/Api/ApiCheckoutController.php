<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\JsonResponse;
use App\Core\Validator;
use App\Services\CartServiceInterface;
use App\Services\AuthServiceInterface;
use App\Services\DeliveryServiceInterface;
use App\Services\OrderServiceInterface;
use App\Services\SettingsServiceInterface;
use App\Services\AddressServiceInterface;
use App\Services\PricingServiceInterface;
use App\Services\VirtualProductServiceInterface;
use App\Services\Payment\PaymentServiceInterface;
use App\Services\CurrencyServiceInterface;

class ApiCheckoutController {
    public function __construct(
        private OrderServiceInterface $orderService,
        private CartServiceInterface $cart,
        private AuthServiceInterface $auth,
        private DeliveryServiceInterface $delivery,
        private SettingsServiceInterface $settings,
        private PaymentServiceInterface $payment,
        private AddressServiceInterface $addressService,
        private PricingServiceInterface $pricingService,
        private Validator $validator,
        private VirtualProductServiceInterface $virtualProductService,
        private CurrencyServiceInterface $currencyService
    ) {}

    /**
     * Get active delivery options.
     */
    public function getDeliveryOptions(Request $request): Response {
        $total = $this->cart->total();
        $user = $this->auth->currentUser();
        $role = $user ? $user->role : null;
        
        $options = $this->delivery->active($total, $role);
        $formatted = [];
        foreach ($options as $opt) {
            $formatted[] = [
                'id' => $opt->id,
                'name' => $opt->name,
                'price' => $this->currencyService->convert((float)$opt->price),
                'min_order_total' => $this->currencyService->convert((float)$opt->min_order_total)
            ];
        }
        
        return new JsonResponse([
            'success' => true,
            'data' => $formatted
        ]);
    }

    /**
     * Get saved addresses for the authenticated user.
     */
    public function getAddresses(Request $request): Response {
        $user = $this->auth->currentUser();
        $addresses = $this->addressService->getByUserId($user->id);

        $formatted = [];
        foreach ($addresses as $addr) {
            $formatted[] = [
                'id' => $addr->id,
                'label' => $addr->label,
                'name' => $addr->name,
                'address' => $addr->address,
                'city' => $addr->city,
                'postcode' => $addr->postcode,
                'country' => $addr->country,
                'is_default' => $addr->isDefault()
            ];
        }

        return new JsonResponse([
            'success' => true,
            'data' => $formatted
        ]);
    }

    /**
     * Create or update a saved address.
     */
    public function saveAddress(Request $request): Response {
        $user = $this->auth->currentUser();
        $post = $request->getPost();

        $rules = [
            'name' => 'required',
            'address' => 'required',
            'city' => 'required',
            'postcode' => 'required',
            'country' => 'required'
        ];

        $errors = $this->validator->check($post, $rules);
        if ($errors) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed.',
                    'details' => $errors
                ]
            ], 400);
        }

        $addressId = (int)($post['id'] ?? 0);
        $savedId = $this->addressService->save($user->id, $post, $addressId);

        return new JsonResponse([
            'success' => true,
            'message' => $addressId > 0 ? 'Address updated.' : 'Address created.',
            'data' => [
                'id' => $savedId
            ]
        ], $addressId > 0 ? 200 : 201);
    }

    /**
     * Process checkout submission.
     */
    public function process(Request $request): Response {
        $items = $this->cart->items();
        if (empty($items)) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'EMPTY_CART',
                    'message' => 'Your cart is empty.'
                ]
            ], 400);
        }

        $post = $request->getPost();
        $name = trim($post['name'] ?? '');
        $email = trim($post['email'] ?? '');
        $address = trim($post['address'] ?? '');
        $city = trim($post['city'] ?? '');
        $postcode = trim($post['postcode'] ?? '');
        $country = trim($post['country'] ?? '');
        $notes = trim($post['notes'] ?? '');
        $deliveryId = (int)($post['delivery_option_id'] ?? 0);
        $giftCardCode = trim($post['gift_card_code'] ?? '');

        $isVirtualOnly = $this->cart->isVirtualOnly();

        if ($isVirtualOnly) {
            $rules = [
                'name' => 'required',
                'email' => 'required|email',
            ];
        } else {
            $rules = [
                'name' => 'required',
                'email' => 'required|email',
                'address' => 'required',
                'city' => 'required',
                'postcode' => 'required',
                'country' => 'required',
                'delivery_option_id' => 'required',
            ];
        }

        $errors = $this->validator->check($post, $rules);

        $deliveryCost = 0.0;
        $deliveryMethod = 'Digital Delivery';
        $fullAddress = 'Digital Delivery';

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

        if ($errors) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed.',
                    'details' => $errors
                ]
            ], 400);
        }

        $user = $this->auth->currentUser();
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
            'user_id' => $user->id ?? null,
            'customer_name' => $name,
            'customer_email' => $email,
            'total' => $total,
            'total_vat_amount' => $totalVat,
            'shipping_address' => $fullAddress,
            'notes' => $finalNotes,
            'delivery_method' => $deliveryMethod,
            'delivery_cost' => $deliveryCost,
            'promotion_id' => $primaryPromo?->id,
            'discount_amount' => $discount,
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

            $activeGateway = $this->settings->get('payment_gateway') ?: 'mock_card';
            try {
                $paymentResult = $this->payment->process($activeGateway, $order, [
                    'card_number' => trim($post['card_number'] ?? ''),
                    'card_expiry' => trim($post['card_expiry'] ?? ''),
                    'card_cvc' => trim($post['card_cvc'] ?? '')
                ]);
            } catch (\RuntimeException $e) {
                $paymentResult = \App\Services\Payment\PaymentResult::failure($e->getMessage(), 'error');
            }
            
            if ($paymentResult->success) {
                $this->orderService->updatePaymentInfo(
                    $order_id, 
                    $activeGateway, 
                    'paid', 
                    $paymentResult->transactionId
                );
                
                $this->orderService->updateStatus(
                    $order_id, 
                    \App\Models\Order::STATUS_PAID, 
                    $user->id ?? null, 
                    'Paid via transaction ' . $paymentResult->transactionId
                );
                
                $this->cart->clear();

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Order placed successfully.',
                    'data' => [
                        'order_id' => $order_id,
                        'order_reference' => $order->getFormattedId(),
                        'status' => \App\Models\Order::STATUS_PAID,
                        'transaction_id' => $paymentResult->transactionId
                    ]
                ], 201);
            } else {
                $this->orderService->updatePaymentInfo($order_id, $activeGateway, 'failed');
                $this->orderService->updateStatus($order_id, \App\Models\Order::STATUS_FAILED, $user->id ?? null, 'Payment failed: ' . $paymentResult->message);
                
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'PAYMENT_FAILED',
                        'message' => 'Payment processing failed: ' . $paymentResult->message,
                        'details' => [
                            'order_id' => $order_id
                        ]
                    ]
                ], 400);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'CHECKOUT_ERROR',
                    'message' => 'Failed to process order: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Get order list for authenticated user.
     */
    public function index(Request $request): Response {
        $user = $this->auth->currentUser();
        $orders = $this->orderService->getForUser($user->id);

        $formatted = [];
        foreach ($orders as $o) {
            $formatted[] = [
                'id' => $o->id,
                'order_reference' => $o->getFormattedId(),
                'total' => $this->currencyService->convert((float)$o->total),
                'status' => $o->status,
                'created_at' => $o->created_at
            ];
        }

        return new JsonResponse([
            'success' => true,
            'data' => $formatted
        ]);
    }

    /**
     * Format a single order for REST response.
     */
    private function formatOrder(\App\Models\Order $order): array {
        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'variant_name' => $item->variant_name,
                'name' => $item->product_name ?? $item->name ?? 'Deleted Product',
                'sku' => $item->sku,
                'qty' => $item->quantity,
                'quantity' => $item->quantity,
                'price' => $this->currencyService->convert((float)$item->unit_price),
                'unit_price' => $this->currencyService->convert((float)$item->unit_price),
                'total' => $this->currencyService->convert((float)$item->getSubtotal()),
                'is_bundle' => (bool)$item->is_bundle,
                'bundle_components' => array_map(fn($bc) => [
                    'qty' => $bc['qty'],
                    'name' => $bc['name']
                ], $item->bundle_components)
            ];
        }

        return [
            'id' => $order->id,
            'order_reference' => $order->getFormattedId(),
            'status' => $order->status,
            'customer_name' => $order->customer_name,
            'name' => $order->customer_name, // alias
            'customer_email' => $order->customer_email,
            'email' => $order->customer_email, // alias
            'total' => $this->currencyService->convert((float)$order->total),
            'total_vat' => $this->currencyService->convert((float)$order->total_vat_amount),
            'discount' => $this->currencyService->convert((float)$order->discount_amount),
            'shipping_address' => $order->shipping_address,
            'delivery_method' => $order->delivery_method,
            'delivery_cost' => $this->currencyService->convert((float)$order->delivery_cost),
            'created_at' => $order->created_at,
            'items' => $items
        ];
    }

    /**
     * Get specific order details.
     */
    public function show(Request $request, string $id): Response {
        $user = $this->auth->currentUser();
        $order = $this->orderService->findById((int)$id);

        if (!$order || ($order->user_id !== $user->id && !$user->isAdmin())) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Order not found.'
                ]
            ], 404);
        }

        return new JsonResponse([
            'success' => true,
            'data' => $this->formatOrder($order)
        ]);
    }

    /**
     * Lookup guest/any order by reference and email.
     */
    public function lookup(Request $request): Response {
        $post = $request->getPost();
        $reference = trim($post['order_reference'] ?? '');
        $email = trim($post['email'] ?? '');

        if (!$reference || !$email) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_INPUT',
                    'message' => 'Order reference and email are required.'
                ]
            ], 400);
        }

        // Parse reference ID from #000123 format or just use int
        $parsedId = (int)ltrim($reference, '#');

        $order = $this->orderService->findById($parsedId);
        if (!$order || strcasecmp(trim($order->customer_email), $email) !== 0) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'No matching order found.'
                ]
            ], 404);
        }

        return new JsonResponse([
            'success' => true,
            'data' => $this->formatOrder($order)
        ]);
    }
}
