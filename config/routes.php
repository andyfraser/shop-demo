<?php

use App\Controllers\StorefrontController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\AdminDashboardController;
use App\Controllers\AdminCategoriesController;
use App\Controllers\AdminProductsController;
use App\Controllers\AdminOrdersController;
use App\Controllers\AdminDeliveryController;
use App\Controllers\AdminUsersController;
use App\Controllers\AdminSettingsController;
use App\Controllers\AdminAttributesController;
use App\Controllers\AdminReturnsController;
use App\Controllers\AdminReviewsController;
use App\Controllers\AccountController;
use App\Controllers\WishlistController;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\VerifiedMiddleware;

$adminMiddleware = [AdminMiddleware::class];
$adminPostMiddleware = [AdminMiddleware::class, CsrfMiddleware::class];
$authMiddleware = [AuthMiddleware::class];
$authPostMiddleware = [AuthMiddleware::class, CsrfMiddleware::class];
$guestMiddleware = [GuestMiddleware::class];
$guestPostMiddleware = [GuestMiddleware::class, CsrfMiddleware::class];
$csrfMiddleware = [CsrfMiddleware::class];
$verifiedMiddleware = [VerifiedMiddleware::class];
$verifiedPostMiddleware = [VerifiedMiddleware::class, CsrfMiddleware::class];

return [
    // Storefront routes
    ['method' => 'GET', 'path' => '/', 'handler' => [StorefrontController::class, 'index']],
    ['method' => 'GET', 'path' => '/products', 'handler' => [StorefrontController::class, 'products']],
    ['method' => 'GET', 'path' => '/search', 'handler' => [StorefrontController::class, 'search']],
    ['method' => 'GET', 'path' => '/category/:slug', 'handler' => [StorefrontController::class, 'category']],
    ['method' => 'GET', 'path' => '/product/:slug', 'handler' => [StorefrontController::class, 'product']],
    ['method' => 'POST', 'path' => '/product/:slug', 'handler' => [CartController::class, 'add'], 'middlewares' => $csrfMiddleware],
    ['method' => 'POST', 'path' => '/product/:slug/review', 'handler' => [StorefrontController::class, 'submitReview'], 'middlewares' => $authPostMiddleware],

    // Auth routes
    ['method' => 'GET', 'path' => '/login', 'handler' => [AuthController::class, 'showLogin'], 'middlewares' => $guestMiddleware],
    ['method' => 'POST', 'path' => '/login', 'handler' => [AuthController::class, 'login'], 'middlewares' => $guestPostMiddleware],
    ['method' => 'GET', 'path' => '/register', 'handler' => [AuthController::class, 'showRegister'], 'middlewares' => $guestMiddleware],
    ['method' => 'POST', 'path' => '/register', 'handler' => [AuthController::class, 'register'], 'middlewares' => $guestPostMiddleware],
    ['method' => 'GET', 'path' => '/verify-email', 'handler' => [AuthController::class, 'verifyEmail']],
    ['method' => 'GET', 'path' => '/verify-email/resend', 'handler' => [AuthController::class, 'resendVerification']],
    ['method' => 'GET', 'path' => '/logout', 'handler' => [AuthController::class, 'logout']],
    
    ['method' => 'GET', 'path' => '/account', 'handler' => [AccountController::class, 'show'], 'middlewares' => $authMiddleware],
    ['method' => 'GET', 'path' => '/account/addresses/new', 'handler' => [AccountController::class, 'newAddress'], 'middlewares' => $authMiddleware],
    ['method' => 'GET', 'path' => '/account/addresses/edit', 'handler' => [AccountController::class, 'editAddress'], 'middlewares' => $authMiddleware],
    ['method' => 'POST', 'path' => '/account/addresses/save', 'handler' => [AccountController::class, 'saveAddress'], 'middlewares' => $authPostMiddleware],
    ['method' => 'POST', 'path' => '/account/addresses/delete', 'handler' => [AccountController::class, 'deleteAddress'], 'middlewares' => $authPostMiddleware],
    ['method' => 'POST', 'path' => '/account/addresses/default', 'handler' => [AccountController::class, 'setDefaultAddress'], 'middlewares' => $authPostMiddleware],
    ['method' => 'POST', 'path' => '/account/cancel-order', 'handler' => [AccountController::class, 'cancelOrder'], 'middlewares' => $authPostMiddleware],
    ['method' => 'POST', 'path' => '/account/request-return', 'handler' => [AccountController::class, 'requestReturn'], 'middlewares' => $authPostMiddleware],
    ['method' => 'GET', 'path' => '/account/orders/:id', 'handler' => [AccountController::class, 'orderDetail'], 'middlewares' => $authMiddleware],

    // Wishlist routes
    ['method' => 'GET', 'path' => '/wishlist', 'handler' => [WishlistController::class, 'index'], 'middlewares' => $authMiddleware],
    ['method' => 'POST', 'path' => '/wishlist/add/:productId', 'handler' => [WishlistController::class, 'add'], 'middlewares' => $authPostMiddleware],
    ['method' => 'POST', 'path' => '/wishlist/remove/:productId', 'handler' => [WishlistController::class, 'remove'], 'middlewares' => $authPostMiddleware],

    // Common icon routes to prevent 404 errors
    ['method' => 'GET', 'path' => '/favicon.ico', 'handler' => [StorefrontController::class, 'handleIcon']],
    ['method' => 'GET', 'path' => '/apple-touch-icon.png', 'handler' => [StorefrontController::class, 'handleIcon']],
    ['method' => 'GET', 'path' => '/apple-touch-icon-precomposed.png', 'handler' => [StorefrontController::class, 'handleIcon']],

    // Cart routes
    ['method' => 'GET', 'path' => '/cart', 'handler' => [CartController::class, 'show']],
    ['method' => 'POST', 'path' => '/cart', 'handler' => [CartController::class, 'update'], 'middlewares' => $csrfMiddleware],

    // Checkout routes
    ['method' => 'GET', 'path' => '/checkout', 'handler' => [CheckoutController::class, 'show'], 'middlewares' => $verifiedMiddleware],
    ['method' => 'POST', 'path' => '/checkout', 'handler' => [CheckoutController::class, 'process'], 'middlewares' => $verifiedPostMiddleware],
    ['method' => 'GET', 'path' => '/order/confirm', 'handler' => [CheckoutController::class, 'confirm']],

    // Admin routes
    ['method' => 'GET', 'path' => '/admin', 'handler' => [AdminDashboardController::class, 'index'], 'middlewares' => $adminMiddleware],
    ['method' => 'GET', 'path' => '/admin/', 'handler' => [AdminDashboardController::class, 'index'], 'middlewares' => $adminMiddleware],
    ['method' => 'GET', 'path' => '/admin/categories', 'handler' => [AdminCategoriesController::class, 'list'], 'middlewares' => $adminMiddleware],
    ['method' => 'GET', 'path' => '/admin/categories/new', 'handler' => [AdminCategoriesController::class, 'create'], 'middlewares' => $adminMiddleware],
    ['method' => 'GET', 'path' => '/admin/categories/edit', 'handler' => [AdminCategoriesController::class, 'edit'], 'middlewares' => $adminMiddleware],
    ['method' => 'POST', 'path' => '/admin/categories/new', 'handler' => [AdminCategoriesController::class, 'save'], 'middlewares' => $adminPostMiddleware],
    ['method' => 'POST', 'path' => '/admin/categories/edit', 'handler' => [AdminCategoriesController::class, 'save'], 'middlewares' => $adminPostMiddleware],
    ['method' => 'GET', 'path' => '/admin/categories/delete', 'handler' => [AdminCategoriesController::class, 'delete'], 'middlewares' => $adminMiddleware],

    ['method' => 'GET', 'path' => '/admin/products', 'handler' => [AdminProductsController::class, 'list'], 'middlewares' => $adminMiddleware],
    ['method' => 'GET', 'path' => '/admin/products/new', 'handler' => [AdminProductsController::class, 'create'], 'middlewares' => $adminMiddleware],
    ['method' => 'GET', 'path' => '/admin/products/edit', 'handler' => [AdminProductsController::class, 'edit'], 'middlewares' => $adminMiddleware],
    ['method' => 'POST', 'path' => '/admin/products/new', 'handler' => [AdminProductsController::class, 'save'], 'middlewares' => $adminPostMiddleware],
    ['method' => 'POST', 'path' => '/admin/products/edit', 'handler' => [AdminProductsController::class, 'save'], 'middlewares' => $adminPostMiddleware],
    ['method' => 'GET', 'path' => '/admin/products/delete', 'handler' => [AdminProductsController::class, 'delete'], 'middlewares' => $adminMiddleware],

    ['method' => 'GET', 'path' => '/admin/orders', 'handler' => [AdminOrdersController::class, 'list'], 'middlewares' => $adminMiddleware],
    ['method' => 'GET', 'path' => '/admin/orders/detail', 'handler' => [AdminOrdersController::class, 'detail'], 'middlewares' => $adminMiddleware],
    ['method' => 'POST', 'path' => '/admin/orders/update-status', 'handler' => [AdminOrdersController::class, 'updateStatus'], 'middlewares' => $adminPostMiddleware],

    ['method' => 'GET', 'path' => '/admin/returns', 'handler' => [AdminReturnsController::class, 'list'], 'middlewares' => $adminMiddleware],
    ['method' => 'GET', 'path' => '/admin/returns/detail', 'handler' => [AdminReturnsController::class, 'detail'], 'middlewares' => $adminMiddleware],
    ['method' => 'POST', 'path' => '/admin/returns/approve', 'handler' => [AdminReturnsController::class, 'approve'], 'middlewares' => $adminPostMiddleware],
    ['method' => 'POST', 'path' => '/admin/returns/reject', 'handler' => [AdminReturnsController::class, 'reject'], 'middlewares' => $adminPostMiddleware],

    ['method' => 'GET', 'path' => '/admin/reviews', 'handler' => [AdminReviewsController::class, 'list'], 'middlewares' => $adminMiddleware],
    ['method' => 'POST', 'path' => '/admin/reviews/update-status', 'handler' => [AdminReviewsController::class, 'updateStatus'], 'middlewares' => $adminPostMiddleware],

    ['method' => 'GET', 'path' => '/admin/delivery', 'handler' => [AdminDeliveryController::class, 'list'], 'middlewares' => $adminMiddleware],
    ['method' => 'GET', 'path' => '/admin/delivery/new', 'handler' => [AdminDeliveryController::class, 'create'], 'middlewares' => $adminMiddleware],
    ['method' => 'GET', 'path' => '/admin/delivery/edit', 'handler' => [AdminDeliveryController::class, 'edit'], 'middlewares' => $adminMiddleware],
    ['method' => 'POST', 'path' => '/admin/delivery/new', 'handler' => [AdminDeliveryController::class, 'save'], 'middlewares' => $adminPostMiddleware],
    ['method' => 'POST', 'path' => '/admin/delivery/edit', 'handler' => [AdminDeliveryController::class, 'save'], 'middlewares' => $adminPostMiddleware],
    ['method' => 'GET', 'path' => '/admin/delivery/delete', 'handler' => [AdminDeliveryController::class, 'delete'], 'middlewares' => $adminMiddleware],

    ['method' => 'GET', 'path' => '/admin/attributes', 'handler' => [AdminAttributesController::class, 'list'], 'middlewares' => $adminMiddleware],
    ['method' => 'GET', 'path' => '/admin/attributes/new', 'handler' => [AdminAttributesController::class, 'create'], 'middlewares' => $adminMiddleware],
    ['method' => 'GET', 'path' => '/admin/attributes/edit', 'handler' => [AdminAttributesController::class, 'edit'], 'middlewares' => $adminMiddleware],
    ['method' => 'POST', 'path' => '/admin/attributes/new', 'handler' => [AdminAttributesController::class, 'save'], 'middlewares' => $adminPostMiddleware],
    ['method' => 'POST', 'path' => '/admin/attributes/edit', 'handler' => [AdminAttributesController::class, 'save'], 'middlewares' => $adminPostMiddleware],
    ['method' => 'GET', 'path' => '/admin/attributes/delete', 'handler' => [AdminAttributesController::class, 'delete'], 'middlewares' => $adminMiddleware],

    ['method' => 'GET', 'path' => '/admin/users', 'handler' => [AdminUsersController::class, 'list'], 'middlewares' => $adminMiddleware],
    ['method' => 'GET', 'path' => '/admin/users/new', 'handler' => [AdminUsersController::class, 'create'], 'middlewares' => $adminMiddleware],
    ['method' => 'GET', 'path' => '/admin/users/edit', 'handler' => [AdminUsersController::class, 'edit'], 'middlewares' => $adminMiddleware],
    ['method' => 'POST', 'path' => '/admin/users/new', 'handler' => [AdminUsersController::class, 'save'], 'middlewares' => $adminPostMiddleware],
    ['method' => 'POST', 'path' => '/admin/users/edit', 'handler' => [AdminUsersController::class, 'save'], 'middlewares' => $adminPostMiddleware],
    ['method' => 'GET', 'path' => '/admin/users/delete', 'handler' => [AdminUsersController::class, 'delete'], 'middlewares' => $adminMiddleware],

    ['method' => 'GET', 'path' => '/admin/settings', 'handler' => [AdminSettingsController::class, 'show'], 'middlewares' => $adminMiddleware],
    ['method' => 'POST', 'path' => '/admin/settings', 'handler' => [AdminSettingsController::class, 'save'], 'middlewares' => $adminPostMiddleware],

    ['method' => 'GET', 'path' => '/admin/backup', 'handler' => [\App\Controllers\AdminBackupController::class, 'index'], 'middlewares' => $adminMiddleware],
    ['method' => 'POST', 'path' => '/admin/backup/download', 'handler' => [\App\Controllers\AdminBackupController::class, 'download'], 'middlewares' => $adminPostMiddleware],
    ['method' => 'POST', 'path' => '/admin/backup/restore', 'handler' => [\App\Controllers\AdminBackupController::class, 'restore'], 'middlewares' => $adminPostMiddleware],
];
