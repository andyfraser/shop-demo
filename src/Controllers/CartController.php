<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\CartService;
use App\Services\SecurityService;

class CartController
{
    public function show()
    {
        Renderer::render('cart', [
            'page_title' => 'Shopping Cart',
            'items' => CartService::items(),
            'total' => CartService::total(),
            'flash_success' => flash('success'),
        ]);
    }

    public function update()
    {
        SecurityService::verifyCsrf();
        if (isset($_POST['update'])) {
            foreach (($_POST['qty'] ?? []) as $id => $qty) {
                CartService::update((int) $id, (int) $qty);
            }
            flash('success', 'Cart updated.');
        }
        if (isset($_POST['remove'])) {
            CartService::remove((int) $_POST['remove']);
        }
        redirect('/cart');
    }

    public function add()
    {
        SecurityService::verifyCsrf();
        $productId = (int) ($_POST['product_id'] ?? 0);
        $slug = $_POST['slug'] ?? '';
        $qty = max(1, (int) ($_POST['qty'] ?? 1));

        CartService::add($productId, $qty);
        // Wait, original logic: flash('success', money($qty * $product['price']) . ' added to your cart.');
        // We will just flash a generic message to save db query, or run query. Let's redirect to product
        flash('success', 'Items added to your cart.');
        redirect('/product?slug=' . urlencode($slug));
    }
}
