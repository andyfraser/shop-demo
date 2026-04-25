<?php
namespace App\Core;

use App\Services\CartService;
use App\Services\AuthService;

class ViewComposer {
    public function __construct(
        private CartService $cart,
        private AuthService $auth
    ) {}

    /**
     * Data available to all storefront templates
     */
    public function getStorefrontGlobals(): array {
        return [
            'cart_count' => $this->cart->count(),
            'current_user' => $this->auth->currentUser(),
            'nav_tree' => get_category_tree(),
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
