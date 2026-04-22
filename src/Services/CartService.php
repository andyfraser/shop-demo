<?php
namespace App\Services;

use App\Core\Database;

class CartService {
    public static function get(): array {
        AuthService::sessionStart();
        return $_SESSION['cart'] ?? [];
    }

    public static function add(int $productId, int $qty = 1): void {
        AuthService::sessionStart();
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $qty;
    }

    public static function remove(int $productId): void {
        AuthService::sessionStart();
        unset($_SESSION['cart'][$productId]);
    }

    public static function update(int $productId, int $qty): void {
        if ($qty <= 0) { self::remove($productId); return; }
        AuthService::sessionStart();
        $_SESSION['cart'][$productId] = $qty;
    }

    public static function clear(): void {
        AuthService::sessionStart();
        $_SESSION['cart'] = [];
    }

    public static function count(): int {
        return array_sum(self::get());
    }

    public static function items(): array {
        $c = self::get();
        if (empty($c)) return [];
        $ids = implode(',', array_map('intval', array_keys($c)));
        $rows = Database::getConnection()->query("SELECT * FROM products WHERE id IN ($ids)")->fetchAll();
        foreach ($rows as &$row) {
            $row['qty'] = $c[$row['id']];
            $row['subtotal'] = $row['price'] * $row['qty'];
            $row['vat_amount'] = $row['subtotal'] * ($row['vat_rate'] / (100 + $row['vat_rate']));
        }
        return $rows;
    }

    public static function total(): float {
        return array_sum(array_column(self::items(), 'subtotal'));
    }

    public static function totalVat(): float {
        return array_sum(array_column(self::items(), 'vat_amount'));
    }
}
