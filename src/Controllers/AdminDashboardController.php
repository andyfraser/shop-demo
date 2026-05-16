<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Renderer;
use App\Services\SettingsServiceInterface;
use App\Services\ProductServiceInterface;
use App\Services\OrderServiceInterface;
use App\Services\UserServiceInterface;

class AdminDashboardController {
    public function __construct(
        private Renderer $renderer,
        private SettingsServiceInterface $settingsService,
        private ProductServiceInterface $productService,
        private OrderServiceInterface $orderService,
        private UserServiceInterface $userService
    ) {}

    public function index(Request $request): Response {
        $stats = [
            'products'  => $this->productService->countAllActive(new \App\Core\QueryCriteria()),
            'customers' => $this->userService->countNonAdmins(),
            'orders'    => $this->orderService->countAll(),
            'revenue'   => $this->orderService->getTotalRevenue(),
        ];

        $recent_orders = $this->orderService->getRecentOrders(10);

        $threshold = (int)$this->settingsService->get('low_stock_threshold');
        $low_stock = $this->productService->getLowStock($threshold, 10, 'stock');
        $low_stock_count = $this->productService->countLowStock($threshold);

        return new HtmlResponse($this->renderer->adminRender('dashboard', [
            'page_title'    => 'Dashboard',
            'active'        => 'dashboard',
            'stats'         => $stats,
            'recent_orders' => $recent_orders,
            'low_stock'     => $low_stock,
            'low_stock_count' => $low_stock_count,
        ]));
    }
}
