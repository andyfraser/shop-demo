<?php
namespace App\Services;

class CartService implements CartServiceInterface {
    public function __construct(
        private ProductServiceInterface $productService,
        private AuthServiceInterface $auth,
        private VatServiceInterface $vatService
    ) {}

    public function get(): array {
        $this->auth->sessionStart();
        return $_SESSION['cart'] ?? [];
    }

    public function add(int $productId, int $qty = 1, ?int $variantId = null): void {
        $this->auth->sessionStart();
        $key = $this->generateKey($productId, $variantId);
        $_SESSION['cart'][$key] = ($_SESSION['cart'][$key] ?? 0) + $qty;
    }

    public function remove(string $key): void {
        $this->auth->sessionStart();
        unset($_SESSION['cart'][$key]);
    }

    public function update(string $key, int $qty): void {
        $this->auth->sessionStart();
        if ($qty <= 0) { $this->remove($key); return; }
        $_SESSION['cart'][$key] = $qty;
    }

    public function clear(): void {
        $this->auth->sessionStart();
        $_SESSION['cart'] = [];
    }

    public function count(): int {
        return array_sum($this->get());
    }

    public function items(): array {
        $cart = $this->get();
        if (empty($cart)) return [];
        
        $productIds = [];
        $variantIds = [];
        
        foreach (array_keys($cart) as $key) {
            $parts = explode('-', $key);
            $productIds[] = (int)$parts[0];
            if (isset($parts[1])) {
                $variantIds[] = (int)$parts[1];
            }
        }

        $products = [];
        foreach ($this->productService->findByIds(array_unique($productIds)) as $p) {
            $products[$p->id] = $p;
        }

        $variants = [];
        if (!empty($variantIds)) {
            foreach ($this->productService->findVariantsByIds(array_unique($variantIds)) as $v) {
                $variants[$v->id] = $v;
            }
        }

        $items = [];
        foreach ($cart as $key => $qty) {
            $parts = explode('-', $key);
            $pid = (int)$parts[0];
            $vid = isset($parts[1]) ? (int)$parts[1] : null;

            if (!isset($products[$pid])) continue;

            $product = $products[$pid];
            $variant = $vid ? ($variants[$vid] ?? null) : null;

            $unitPrice = $variant ? $variant->getEffectivePrice($product->price) : $product->price;
            $subtotal = $unitPrice * $qty;
            $vatAmount = $this->vatService->calculateVatFromGross($subtotal, $product->vat_rate);

            $items[] = [
                'key'        => $key,
                'product'    => $product,
                'variant'    => $variant,
                'qty'        => $qty,
                'unit_price' => $unitPrice,
                'subtotal'   => $subtotal,
                'vat_amount' => $vatAmount,
            ];
        }
        return $items;
    }

    public function total(): float {
        return array_sum(array_column($this->items(), 'subtotal'));
    }

    public function totalVat(): float {
        return array_sum(array_column($this->items(), 'vat_amount'));
    }

    private function generateKey(int $productId, ?int $variantId = null): string {
        return $variantId ? "{$productId}-{$variantId}" : (string)$productId;
    }
}
