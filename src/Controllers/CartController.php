<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\JsonResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Renderer;
use App\Services\CartServiceInterface;
use App\Services\SecurityServiceInterface;
use App\Services\ProductServiceInterface;

class CartController
{
    public function __construct(
        private Renderer $renderer,
        private CartServiceInterface $cartService,
        private SecurityServiceInterface $securityService,
        private ProductServiceInterface $productService
    ) {}

    public function show(Request $request): Response
    {
        return new HtmlResponse($this->renderer->render('cart', [
            'page_title' => 'Shopping Cart',
            'cartService' => $this->cartService,
            'items' => $this->cartService->items(),
            'total' => $this->cartService->total(),
            'total_vat' => $this->cartService->totalVat(),
            'discount' => $this->cartService->discount(),
            'grand_total' => $this->cartService->grandTotal(),
            'applied_promotions' => $this->cartService->getAppliedPromotions(),
            'flash_success' => flash('success'),
            'flash_error' => flash('error'),
        ]));
    }

    public function applyPromo(Request $request): Response
    {
        if ($request->getPost('remove_promo')) {
            $code = trim($request->getPost('promo_code', ''));
            $this->cartService->removePromoCode($code ?: null);
            flash('success', 'Promo code removed.');
        } else {
            $code = trim($request->getPost('promo_code', ''));
            if ($this->cartService->applyPromoCode($code)) {
                flash('success', 'Promo code applied!');
            } else {
                flash('error', 'Invalid or expired promo code.');
            }
        }
        return new RedirectResponse('/cart');
    }

    public function update(Request $request): Response
    {
        $message = 'Cart updated.';

        if ($request->getPost('update')) {
            foreach (($request->getPost('qty', [])) as $key => $qty) {
                $this->cartService->update((string) $key, (int) $qty);
            }
        }
        if ($request->getPost('remove')) {
            $this->cartService->remove((string) $request->getPost('remove'));
            $message = 'Item removed.';
        }

        if ($request->isAjax()) {
            $items = $this->cartService->items();
            $lineItems = array_map(fn($i) => [
                'key'        => $i->key,
                'subtotal'   => money($i->getSubtotal()),
                'unit_price' => money($i->unit_price),
            ], $items);

            $promos = $this->cartService->getAppliedPromotions();
            $promoNames = array_map(fn($p) => $p->name, $promos);

            return new JsonResponse([
                'ok'         => true,
                'cart_count' => $this->cartService->count(),
                'subtotal'   => money($this->cartService->total()),
                'discount'   => money($this->cartService->discount()),
                'grand_total'=> money($this->cartService->grandTotal()),
                'total_vat'  => money($this->cartService->totalVat()),
                'has_discount'=> $this->cartService->discount() > 0,
                'promo_names' => $promoNames,
                'items'      => $lineItems,
                'message'    => $message,
            ]);
        }

        if ($request->getPost('update')) {
            flash('success', $message);
        }
        return new RedirectResponse('/cart');
    }

    public function add(Request $request, $slug = ''): Response
    {
        $productId = (int) ($request->getPost('product_id', 0));
        $variantId = $request->getPost('variant_id') !== null && $request->getPost('variant_id') !== '' ? (int)$request->getPost('variant_id') : null;
        $slug = $slug ?: ($request->getPost('slug', ''));
        $qty = max(1, (int) ($request->getPost('qty', 1)));

        $product = $this->productService->findById($productId);
        if (!$product) {
            flash('error', 'Product not found.');
            return new RedirectResponse('/');
        }
        $metadata = null;

        if ($product->is_virtual && $product->virtual_type === 'giftcard') {
            $recipientEmail = trim($request->getPost('recipient_email', ''));
            $senderName = trim($request->getPost('sender_name', ''));
            $message = trim($request->getPost('message', ''));

            if ($recipientEmail === '') {
                $errorMsg = 'Recipient email is required for gift cards.';
                if ($request->isAjax()) {
                    return new JsonResponse([
                        'ok' => false,
                        'message' => $errorMsg,
                    ]);
                }
                flash('error', $errorMsg);
                return new RedirectResponse('/product/' . urlencode($slug));
            }

            if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                $errorMsg = 'Please enter a valid recipient email address.';
                if ($request->isAjax()) {
                    return new JsonResponse([
                        'ok' => false,
                        'message' => $errorMsg,
                    ]);
                }
                flash('error', $errorMsg);
                return new RedirectResponse('/product/' . urlencode($slug));
            }

            $metadata = [
                'recipient_email' => $recipientEmail,
                'sender_name' => $senderName,
                'message' => $message,
            ];
        }

        $this->cartService->add($productId, $qty, $variantId, $metadata);

        if ($request->isAjax()) {
            return new JsonResponse([
                'ok'         => true,
                'cart_count' => $this->cartService->count(),
                'message'    => 'Items added to your cart.',
            ]);
        }

        flash('success', 'Items added to your cart.');
        return new RedirectResponse('/product/' . urlencode($slug));
    }
}
