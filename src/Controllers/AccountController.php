<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Renderer;
use App\Services\AuthService;

class AccountController {
    public function show() {
        $user = AuthService::currentUser();
        
        $orders = Database::getConnection()->prepare(
            "SELECT o.*, COUNT(oi.id) as item_count
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE o.user_id = ?
             GROUP BY o.id
             ORDER BY o.created_at DESC"
        );
        $orders->execute([$user['id']]);
        
        Renderer::render('account', [
            'page_title' => 'My Account',
            'orders'     => $orders->fetchAll(),
        ]);
    }
}
