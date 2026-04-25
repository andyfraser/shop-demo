<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/src/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/src/Helpers.php';

// Load configuration
$config = [];
if (file_exists(__DIR__ . '/config/config.php')) {
    $config = require __DIR__ . '/config/config.php';
}

// Database configuration
$dbConfig = $config['db'] ?? [
    'driver' => 'sqlite',
    'path'   => __DIR__ . '/shop.db',
];
define('DB_CONFIG', $dbConfig);

use App\Core\Container;
use App\Core\Router;

$container = new Container();

// Register Logger
$container->set(\Psr\Log\LoggerInterface::class, function() use ($config) {
    $isDebug = $config['app']['debug'] ?? false;
    $retention = $config['app']['log_retention_days'] ?? 30;
    return new \App\Core\FileLogger(__DIR__ . '/logs/app.log', $isDebug, $retention);
});

// Register PDO as a singleton service
$container->set(\PDO::class, function() {
    return \App\Core\Database::getConnection();
});

// Other constants
define('BASE_URL', $config['site']['base_url'] ?? '');
$settings = $container->get(\App\Services\SettingsService::class);
define('SITE_NAME', $settings->get('site_name'));
define('SITE_NAME_PLAIN', str_replace('|', '', SITE_NAME));

$router = new Router($container);

// Load and register routes
$routes = require __DIR__ . '/config/routes.php';
foreach ($routes as $route) {
    $router->add(
        $route['method'],
        $route['path'],
        $route['handler'],
        $route['middlewares'] ?? []
    );
}

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
