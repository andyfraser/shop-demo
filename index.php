<?php

require_once __DIR__ . '/src/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/src/Helpers.php';

// Load configuration
$config = [];
if (file_exists(__DIR__ . '/config/config.php')) {
    $config = require __DIR__ . '/config/config.php';
}

$isDebug = $config['app']['debug'] ?? false;

if (!$isDebug) {
    ini_set('display_errors', 0);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}
error_reporting(E_ALL);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error and Exception Handling
$errorHandler = function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
};

$exceptionHandler = function ($exception) use ($isDebug, $config) {
    // Attempt to log the error
    try {
        $logFile = $config['app']['log_path'] ?? __DIR__ . '/logs/app.log';
        $date = date('Y-m-d H:i:s');
        $logEntry = sprintf("[%s] CRITICAL: Uncaught Exception: %s in %s on line %d" . PHP_EOL, 
            $date, $exception->getMessage(), $exception->getFile(), $exception->getLine());
        $logEntry .= $exception->getTraceAsString() . PHP_EOL;
        
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    } catch (\Exception $e) {
        // If logging fails, we can't do much more
    }

    if ($isDebug) {
        echo "<h1>Fatal Error</h1>";
        echo "<p>" . $exception->getMessage() . "</p>";
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
    } else {
        http_response_code(500);
        // Try to use the renderer for a nice 500 page if possible
        try {
            // We need a container and renderer. This might fail if the error happened early.
            global $container;
            if (isset($container) && $container instanceof \App\Core\Container) {
                $renderer = $container->get(\App\Core\Renderer::class);
                $renderer->render('500');
            } else {
                include __DIR__ . '/templates/500.php';
            }
        } catch (\Exception $e) {
            echo "<h1>500 Internal Server Error</h1>";
            echo "<p>Something went wrong. Please try again later.</p>";
        }
    }
    exit(1);
};

set_error_handler($errorHandler);
set_exception_handler($exceptionHandler);

// Database configuration
$dbConfig = $config['db'] ?? [
    'driver' => 'sqlite',
    'path'   => __DIR__ . '/shop.db',
];
define('DB_CONFIG', $dbConfig);

use App\Core\Container;
use App\Core\Router;

$container = new Container();

// Register services
$servicesFactory = require __DIR__ . '/config/services.php';
$services = $servicesFactory($config);
foreach ($services as $id => $factory) {
    $container->set($id, $factory);
}

// Load settings and define core constants
$settings = $container->get(\App\Services\SettingsService::class);

if (!defined('BASE_URL')) {
    $baseUrlSetting = $settings->get('base_url');
    define('BASE_URL', $baseUrlSetting !== null ? (string)$baseUrlSetting : ($config['site']['base_url'] ?? ''));
}

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
