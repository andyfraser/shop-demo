<?php
namespace App\Core;

use App\Services\CartService;
use App\Services\AuthService;
use App\Services\CategoryService;

class ViewComposer {
    public function __construct(
        private CartService $cart,
        private AuthService $auth,
        private CategoryService $categoryService
    ) {}

    /**
     * Data available to all storefront templates
     */
    public function getStorefrontGlobals(): array {
        return [
            'cart_count' => $this->cart->count(),
            'current_user' => $this->auth->currentUser(),
            'nav_tree' => $this->categoryService->getTree(),
        ];
    }

    /**
     * Data available to all admin templates
     */
    public function getAdminGlobals(): array {
        return [
            'current_user' => $this->auth->currentUser(),
        ];
    }
}
