<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\CartServiceInterface;
use App\Services\SecurityServiceInterface;

class CartController
{
    public function __construct(
        private Renderer $renderer,
        private CartServiceInterface $cartService,
        private SecurityServiceInterface $securityService
    ) {}

    public function show()
    {
        $this->renderer->render('cart', [
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
        ]);
    }

    public function applyPromo()
    {
        if (isset($_POST['remove_promo'])) {
            $code = trim($_POST['promo_code'] ?? '');
            $this->cartService->removePromoCode($code ?: null);
            flash('success', 'Promo code removed.');
        } else {
            $code = trim($_POST['promo_code'] ?? '');
            if ($this->cartService->applyPromoCode($code)) {
                flash('success', 'Promo code applied!');
            } else {
                flash('error', 'Invalid or expired promo code.');
            }
        }
        redirect('/cart');
    }

    public function update()
    {
        $message = 'Cart updated.';

        if (isset($_POST['update'])) {
            foreach (($_POST['qty'] ?? []) as $key => $qty) {
                $this->cartService->update((string) $key, (int) $qty);
            }
        }
        if (isset($_POST['remove'])) {
            $this->cartService->remove((string) $_POST['remove']);
            $message = 'Item removed.';
        }

        if (is_ajax()) {
            $items = $this->cartService->items();
            $lineItems = array_map(fn($i) => [
                'key'      => $i->key,
                'subtotal' => money($i->getSubtotal()),
            ], $items);

            $promos = $this->cartService->getAppliedPromotions();
            $promoNames = array_map(fn($p) => $p->name, $promos);

            header('Content-Type: application/json');
            echo json_encode([
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
            exit;
        }

        if (isset($_POST['update'])) {
            flash('success', $message);
        }
        redirect('/cart');
    }

    public function add($slug = '')
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $variantId = isset($_POST['variant_id']) && $_POST['variant_id'] !== '' ? (int)$_POST['variant_id'] : null;
        $slug = $slug ?: ($_POST['slug'] ?? '');
        $qty = max(1, (int) ($_POST['qty'] ?? 1));

        $this->cartService->add($productId, $qty, $variantId);

        if (is_ajax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'ok'         => true,
                'cart_count' => $this->cartService->count(),
                'message'    => 'Items added to your cart.',
            ]);
            exit;
        }

        flash('success', 'Items added to your cart.');
        redirect('/product/' . urlencode($slug));
    }
}
