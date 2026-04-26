<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\SettingsService;
use App\Services\ProductService;
use App\Services\OrderService;
use App\Services\UserService;

class AdminDashboardController {
    public function __construct(
        private Renderer $renderer,
        private SettingsService $settingsService,
        private ProductService $productService,
        private OrderService $orderService,
        private UserService $userService
    ) {}

    public function index() {
        $stats = [
            'products'  => $this->productService->countAllActive(),
            'customers' => $this->userService->countByRole('customer'),
            'orders'    => $this->orderService->countAll(),
            'revenue'   => $this->orderService->getTotalRevenue(),
        ];

        $recent_orders = $this->orderService->getRecentOrders(10);

        $threshold = (int)$this->settingsService->get('low_stock_threshold');
        $low_stock = $this->productService->getLowStock($threshold);

        $this->renderer->adminRender('dashboard', [
            'page_title'    => 'Dashboard',
            'active'        => 'dashboard',
            'stats'         => $stats,
            'recent_orders' => $recent_orders,
            'low_stock'     => $low_stock,
        ]);
    }
}
