<?php
namespace App\Services;

class CartService implements CartServiceInterface {
    public function __construct(
        private ProductServiceInterface $productService,
        private AuthServiceInterface $auth
    ) {}

    public function get(): array {
        $this->auth->sessionStart();
        return $_SESSION['cart'] ?? [];
    }

    public function add(int $productId, int $qty = 1): void {
        $this->auth->sessionStart();
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $qty;
    }

    public function remove(int $productId): void {
        $this->auth->sessionStart();
        unset($_SESSION['cart'][$productId]);
    }

    public function update(int $productId, int $qty): void {
        if ($qty <= 0) { $this->remove($productId); return; }
        $this->auth->sessionStart();
        $_SESSION['cart'][$productId] = $qty;
    }

    public function clear(): void {
        $this->auth->sessionStart();
        $_SESSION['cart'] = [];
    }

    public function count(): int {
        return array_sum($this->get());
    }

    public function items(): array {
        $c = $this->get();
        if (empty($c)) return [];
        
        $ids = array_map('intval', array_keys($c));
        $products = $this->productService->findByIds($ids);

        $items = [];
        foreach ($products as $p) {
            $qty = $c[$p->id];
            $items[] = [
                'product'    => $p,
                'qty'        => $qty,
                'subtotal'   => $p->getSubtotal($qty),
                'vat_amount' => $p->getVatAmount($qty),
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
}
