<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/src/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/src/Helpers.php';

// Load configuration
$config = [];
if (file_exists(__DIR__ . '/config.php')) {
    $config = require __DIR__ . '/config.php';
}

// Database configuration
$dbConfig = $config['db'] ?? [
    'driver' => 'sqlite',
    'path'   => __DIR__ . '/shop.db',
];
define('DB_CONFIG', $dbConfig);

// Other constants
define('BASE_URL', $config['site']['base_url'] ?? '');
define('SITE_NAME', \App\Services\SettingsService::get('site_name'));
define('SITE_NAME_PLAIN', str_replace('|', '', SITE_NAME));

use App\Core\Router;
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
use App\Controllers\AccountController;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;

$router = new Router();

// Storefront routes
$router->get('/', [StorefrontController::class, 'index']);
$router->get('/products', [StorefrontController::class, 'products']);
$router->get('/search', [StorefrontController::class, 'search']);
$router->get('/category/:slug', [StorefrontController::class, 'category']);
$router->get('/product/:slug', [StorefrontController::class, 'product']);
$router->post('/product/:slug', [CartController::class, 'add']); // Add to cart form posts here

// Auth routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/verify-email', [AuthController::class, 'verifyEmail']);
$router->get('/verify-email/resend', [AuthController::class, 'resendVerification']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/account', [AccountController::class, 'show'], [AuthMiddleware::class]);
$router->post('/account/address', [AccountController::class, 'saveAddress'], [AuthMiddleware::class]);
$router->post('/account/cancel-order', [AccountController::class, 'cancelOrder'], [AuthMiddleware::class]);

// Common icon routes to prevent 404 errors
$router->get('/favicon.ico', [StorefrontController::class, 'handleIcon']);
$router->get('/apple-touch-icon.png', [StorefrontController::class, 'handleIcon']);
$router->get('/apple-touch-icon-precomposed.png', [StorefrontController::class, 'handleIcon']);

// Cart routes
$router->get('/cart', [CartController::class, 'show']);
$router->post('/cart', [CartController::class, 'update']);

// Checkout routes
$router->get('/checkout', [CheckoutController::class, 'show']);
$router->post('/checkout', [CheckoutController::class, 'process']);
$router->get('/order/confirm', [CheckoutController::class, 'confirm']);

// Admin routes
$adminMiddleware = [AdminMiddleware::class];
$router->get('/admin', [AdminDashboardController::class, 'index'], $adminMiddleware);
$router->get('/admin/', [AdminDashboardController::class, 'index'], $adminMiddleware);
$router->get('/admin/categories', [AdminCategoriesController::class, 'list'], $adminMiddleware);
$router->get('/admin/categories/new', [AdminCategoriesController::class, 'create'], $adminMiddleware);
$router->get('/admin/categories/edit', [AdminCategoriesController::class, 'edit'], $adminMiddleware);
$router->post('/admin/categories/new', [AdminCategoriesController::class, 'save'], $adminMiddleware);
$router->post('/admin/categories/edit', [AdminCategoriesController::class, 'save'], $adminMiddleware);
$router->get('/admin/categories/delete', [AdminCategoriesController::class, 'delete'], $adminMiddleware);

$router->get('/admin/products', [AdminProductsController::class, 'list'], $adminMiddleware);
$router->get('/admin/products/new', [AdminProductsController::class, 'create'], $adminMiddleware);
$router->get('/admin/products/edit', [AdminProductsController::class, 'edit'], $adminMiddleware);
$router->post('/admin/products/new', [AdminProductsController::class, 'save'], $adminMiddleware);
$router->post('/admin/products/edit', [AdminProductsController::class, 'save'], $adminMiddleware);
$router->get('/admin/products/delete', [AdminProductsController::class, 'delete'], $adminMiddleware);

$router->get('/admin/orders', [AdminOrdersController::class, 'list'], $adminMiddleware);
$router->get('/admin/orders/detail', [AdminOrdersController::class, 'detail'], $adminMiddleware);
$router->post('/admin/orders/detail', [AdminOrdersController::class, 'updateStatus'], $adminMiddleware);

$router->get('/admin/delivery', [AdminDeliveryController::class, 'list'], $adminMiddleware);
$router->get('/admin/delivery/new', [AdminDeliveryController::class, 'create'], $adminMiddleware);
$router->get('/admin/delivery/edit', [AdminDeliveryController::class, 'edit'], $adminMiddleware);
$router->post('/admin/delivery/new', [AdminDeliveryController::class, 'save'], $adminMiddleware);
$router->post('/admin/delivery/edit', [AdminDeliveryController::class, 'save'], $adminMiddleware);
$router->get('/admin/delivery/delete', [AdminDeliveryController::class, 'delete'], $adminMiddleware);

$router->get('/admin/users', [AdminUsersController::class, 'list'], $adminMiddleware);
$router->get('/admin/users/new', [AdminUsersController::class, 'create'], $adminMiddleware);
$router->get('/admin/users/edit', [AdminUsersController::class, 'edit'], $adminMiddleware);
$router->post('/admin/users/new', [AdminUsersController::class, 'save'], $adminMiddleware);
$router->post('/admin/users/edit', [AdminUsersController::class, 'save'], $adminMiddleware);
$router->get('/admin/users/delete', [AdminUsersController::class, 'delete'], $adminMiddleware);

$router->get('/admin/settings',  [AdminSettingsController::class, 'show'], $adminMiddleware);
$router->post('/admin/settings', [AdminSettingsController::class, 'save'], $adminMiddleware);

$router->get('/admin/backup',    [App\Controllers\AdminBackupController::class, 'index'], $adminMiddleware);
$router->post('/admin/backup/download', [App\Controllers\AdminBackupController::class, 'download'], $adminMiddleware);
$router->post('/admin/backup/restore',  [App\Controllers\AdminBackupController::class, 'restore'], $adminMiddleware);

// Handle request
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Serve static files via built-in server correctly
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($uri, PHP_URL_PATH);
    if ($path !== '/' && file_exists(__DIR__ . '/public' . $path)) {
        return false;
    }
    // Also check if they mistakenly request the root file directly
    if (file_exists(__DIR__ . $path) && is_file(__DIR__ . $path) && $path !== '/index.php') {
        return false;
    }
}

$router->dispatch($uri, $method);
