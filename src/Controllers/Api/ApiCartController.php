<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\JsonResponse;
use App\Services\CartServiceInterface;
use App\Services\ProductServiceInterface;
use App\Services\ImageServiceInterface;
use App\Services\CurrencyServiceInterface;

class ApiCartController {
    public function __construct(
        private CartServiceInterface $cartService,
        private ProductServiceInterface $productService,
        private ImageServiceInterface $imageService,
        private CurrencyServiceInterface $currencyService
    ) {}

    public function show(Request $request): Response {
        return $this->serializeCartResponse();
    }

    public function add(Request $request): Response {
        $productId = (int)$request->getPost('product_id');
        $qty = (int)$request->getPost('quantity', 1);
        $variantId = $request->getPost('variant_id') ? (int)$request->getPost('variant_id') : null;
        $metadata = $request->getPost('metadata') ?: null;

        if (!$productId || $qty <= 0) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_INPUT',
                    'message' => 'Product ID and a positive quantity are required.'
                ]
            ], 400);
        }

        $product = $this->productService->findById($productId);
        if (!$product || !$product->active) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Product not found or inactive.'
                ]
            ], 404);
        }

        // Verify stock for variants
        if ($variantId) {
            $variant = $this->productService->findVariantById($variantId);
            if (!$variant || $variant->stock < $qty) {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'OUT_OF_STOCK',
                        'message' => 'Requested quantity exceeds available variant stock.'
                    ]
                ], 400);
            }
        } elseif ($product->stock < $qty) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'OUT_OF_STOCK',
                    'message' => 'Requested quantity exceeds available stock.'
                ]
            ], 400);
        }

        $this->cartService->add($productId, $qty, $variantId, $metadata);

        return $this->serializeCartResponse('Product added to cart.');
    }

    public function update(Request $request): Response {
        $key = $request->getPost('key');
        $qty = (int)$request->getPost('quantity', 0);

        if (!$key) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_INPUT',
                    'message' => 'Cart item key is required.'
                ]
            ], 400);
        }

        $this->cartService->update($key, $qty);

        return $this->serializeCartResponse('Cart updated successfully.');
    }

    public function applyPromo(Request $request): Response {
        $code = trim($request->getPost('promo_code', ''));
        $remove = (bool)$request->getPost('remove_promo', false);

        if ($remove) {
            $this->cartService->removePromoCode($code ?: null);
            return $this->serializeCartResponse('Promo code removed.');
        }

        if (!$code) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_INPUT',
                    'message' => 'Promo code is required.'
                ]
            ], 400);
        }

        if ($this->cartService->applyPromoCode($code)) {
            return $this->serializeCartResponse('Promo code applied successfully.');
        }

        return new JsonResponse([
            'success' => false,
            'error' => [
                'code' => 'INVALID_PROMO',
                'message' => 'Invalid or expired promo code.'
            ]
        ], 400);
    }

    private function serializeCartResponse(string $message = ''): Response {
        $items = $this->cartService->items();
        
        $formattedItems = [];
        foreach ($items as $item) {
            $formattedItems[] = [
                'key' => $item->key,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'name' => $item->name,
                'sku' => $item->sku,
                'image' => $this->imageService->getUrl($item->image, 'original'),
                'image_thumb' => $this->imageService->getUrl($item->image, 'thumb'),
                'image_medium' => $this->imageService->getUrl($item->image, 'medium'),
                'image_large' => $this->imageService->getUrl($item->image, 'large'),
                'quantity' => $item->qty,
                'unit_price' => $this->currencyService->convert((float)$item->unit_price),
                'subtotal' => $this->currencyService->convert((float)$item->getSubtotal()),
                'is_virtual' => (bool)($item->product->is_virtual ?? false)
            ];
        }

        $promos = [];
        foreach ($this->cartService->getAppliedPromotions() as $p) {
            $promos[] = [
                'name' => $p->name,
                'code' => $p->code,
                'discount' => $this->currencyService->convert((float)$this->cartService->getPromotionDiscount($p))
            ];
        }

        $data = [
            'items' => $formattedItems,
            'applied_promotions' => $promos,
            'summary' => [
                'subtotal' => $this->currencyService->convert((float)$this->cartService->total()),
                'discount' => $this->currencyService->convert((float)$this->cartService->discount()),
                'total_vat' => $this->currencyService->convert((float)$this->cartService->totalVat()),
                'grand_total' => $this->currencyService->convert((float)$this->cartService->grandTotal()),
                'item_count' => $this->cartService->count()
            ],
            'currency' => [
                'code' => $this->currencyService->getCurrentCurrency()->code,
                'symbol' => $this->currencyService->getCurrentCurrency()->symbol
            ]
        ];

        if ($message !== '') {
            return new JsonResponse([
                'success' => true,
                'message' => $message,
                'data' => $data
            ]);
        }

        return new JsonResponse([
            'success' => true,
            'data' => $data
        ]);
    }
}
