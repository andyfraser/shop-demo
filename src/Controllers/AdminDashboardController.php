<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Renderer;

class AdminDashboardController {
    public function index() {
        $db = Database::getConnection();

        $stats = [
            'products'  => $db->query("SELECT COUNT(*) FROM products WHERE active = 1")->fetchColumn(),
            'customers' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
            'orders'    => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
            'revenue'   => $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn(),
        ];

        $recent_orders = $db->query(
            "SELECT o.*, u.name as user_name
             FROM orders o
             LEFT JOIN users u ON o.user_id = u.id
             ORDER BY o.created_at DESC
             LIMIT 10"
        )->fetchAll();

        $low_stock = $db->prepare(
            "SELECT name, stock FROM products WHERE active = 1 AND stock <= ? ORDER BY stock ASC LIMIT 10"
        );
        $low_stock->execute([(int)\App\Services\SettingsService::get('low_stock_threshold')]);
        $low_stock = $low_stock->fetchAll();

        Renderer::adminRender('dashboard', [
            'page_title'    => 'Dashboard',
            'active'        => 'dashboard',
            'stats'         => $stats,
            'recent_orders' => $recent_orders,
            'low_stock'     => $low_stock,
        ]);
    }
}
