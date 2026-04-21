<?php
namespace App\Core;

use App\Services\CartService;
use App\Services\AuthService;

class ViewComposer {
    /**
     * Data available to all storefront templates
     */
    public static function getStorefrontGlobals(): array {
        return [
            'cart_count' => CartService::count(),
            'current_user' => AuthService::currentUser(),
            'nav_tree' => get_category_tree(),
        ];
    }

    /**
     * Data available to all admin templates
     */
    public static function getAdminGlobals(): array {
        return [
            'current_user' => AuthService::currentUser(),
        ];
    }
}
