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
        if (is_ajax()) {
            $this->verifyCsrfAjax();
        } else {
            SecurityService::verifyCsrf();
        }

        $message = 'Cart updated.';

        if (isset($_POST['update'])) {
            foreach (($_POST['qty'] ?? []) as $id => $qty) {
                CartService::update((int) $id, (int) $qty);
            }
        }
        if (isset($_POST['remove'])) {
            CartService::remove((int) $_POST['remove']);
            $message = 'Item removed.';
        }

        if (is_ajax()) {
            $items = CartService::items();
            $lineItems = array_map(fn($i) => [
                'id'       => $i['id'],
                'subtotal' => money($i['subtotal']),
            ], $items);

            header('Content-Type: application/json');
            echo json_encode([
                'ok'         => true,
                'cart_count' => CartService::count(),
                'total'      => money(CartService::total()),
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
        if (is_ajax()) {
            $this->verifyCsrfAjax();
        } else {
            SecurityService::verifyCsrf();
        }

        $productId = (int) ($_POST['product_id'] ?? 0);
        $slug = $slug ?: ($_POST['slug'] ?? '');
        $qty = max(1, (int) ($_POST['qty'] ?? 1));

        CartService::add($productId, $qty);

        if (is_ajax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'ok'         => true,
                'cart_count' => CartService::count(),
                'message'    => 'Items added to your cart.',
            ]);
            exit;
        }

        flash('success', 'Items added to your cart.');
        redirect('/product/' . urlencode($slug));
    }

    private function verifyCsrfAjax(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $passed = $_POST['csrf_token'] ?? '';
        $stored = $_SESSION['csrf_token'] ?? '';
        if (empty($passed) || !hash_equals($stored, $passed)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => 'Invalid CSRF token.']);
            exit;
        }
    }
}
