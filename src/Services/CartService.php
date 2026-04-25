<?php
namespace App\Services;

use App\Core\Database;

class CartService {
    private \PDO $db;
    private AuthService $auth;

    public function __construct(\PDO $db, AuthService $auth) {
        $this->db = $db;
        $this->auth = $auth;
    }

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
        $ids = implode(',', array_map('intval', array_keys($c)));
        $rows = $this->db->query("SELECT * FROM products WHERE id IN ($ids)")->fetchAll();
        foreach ($rows as &$row) {
            $row['qty'] = $c[$row['id']];
            $row['subtotal'] = $row['price'] * $row['qty'];
            $row['vat_amount'] = $row['subtotal'] * ($row['vat_rate'] / (100 + $row['vat_rate']));
        }
        return $rows;
    }

    public function total(): float {
        return array_sum(array_column($this->items(), 'subtotal'));
    }

    public function totalVat(): float {
        return array_sum(array_column($this->items(), 'vat_amount'));
    }
}
