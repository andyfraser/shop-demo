<?php
namespace App\Core;

use App\Services\CartServiceInterface;
use App\Services\AuthServiceInterface;
use App\Services\CategoryServiceInterface;

class ViewComposer {
    public function __construct(
        private CartServiceInterface $cart,
        private AuthServiceInterface $auth,
        private CategoryServiceInterface $categoryService
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
