<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\SettingsService;

class AdminDashboardController {
    private \PDO $db;
    private Renderer $renderer;
    private SettingsService $settingsService;

    public function __construct(\PDO $db, Renderer $renderer, SettingsService $settingsService) {
        $this->db = $db;
        $this->renderer = $renderer;
        $this->settingsService = $settingsService;
    }

    public function index() {
        $stats = [
            'products'  => $this->db->query("SELECT COUNT(*) FROM products WHERE active = 1")->fetchColumn(),
            'customers' => $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
            'orders'    => $this->db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
            'revenue'   => $this->db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn(),
        ];

        $recent_orders = $this->db->query(
            "SELECT o.*, u.name as user_name
             FROM orders o
             LEFT JOIN users u ON o.user_id = u.id
             ORDER BY o.created_at DESC
             LIMIT 10"
        )->fetchAll();

        $low_stock = $this->db->prepare(
            "SELECT name, stock FROM products WHERE active = 1 AND stock <= ? ORDER BY stock ASC LIMIT 10"
        );
        $low_stock->execute([(int)$this->settingsService->get('low_stock_threshold')]);
        $low_stock = $low_stock->fetchAll();

        $this->renderer->adminRender('dashboard', [
            'page_title'    => 'Dashboard',
            'active'        => 'dashboard',
            'stats'         => $stats,
            'recent_orders' => $recent_orders,
            'low_stock'     => $low_stock,
        ]);
    }
}
