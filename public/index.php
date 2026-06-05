<?php

// Force UTC for all internal operations
date_default_timezone_set('UTC');

require_once __DIR__ . '/../src/Core/Autoloader.php';
\App\Core\Autoloader::register();
// Initialize DebugCollector for metrics
\App\Core\DebugCollector::getInstance();

require_once __DIR__ . '/../src/Helpers.php';

// Load configuration
$config = [];
if (file_exists(__DIR__ . '/../config/config.php')) {
    $config = require __DIR__ . '/../config/config.php';
}

$isDebug = $config['app']['debug'] ?? false;
define('DEBUG_MODE', $isDebug);

if ($isDebug) {
    $slowThreshold = $config['app']['slow_query_threshold'] ?? 10.0;
    \App\Core\DebugCollector::getInstance()->setSlowQueryThreshold((float)$slowThreshold);
}

if (!$isDebug) {
    ini_set('display_errors', 0);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}
error_reporting(E_ALL);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
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
        $logFile = $config['app']['error_log_path'] ?? __DIR__ . '/../logs/error.log';
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
                include __DIR__ . '/../templates/500.php';
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
    'path'   => __DIR__ . '/../shop.db',
];
define('DB_CONFIG', $dbConfig);

use App\Core\Container;
use App\Core\Router;

try {
    $container = new Container();
    
    // Instantiate and register Request early so it can be injected
    $request = \App\Core\Request::createFromGlobals();
    $container->set(\App\Core\Request::class, fn() => $request);

    // Register services
    $servicesFactory = require __DIR__ . '/../config/services.php';
    $services = $servicesFactory($config);
    foreach ($services as $id => $factory) {
        $container->set($id, $factory);
    }

    // Force database connection and check if initialized
    $db = $container->get(\PDO::class);
    
    // Check if the settings table exists as a proxy for "is initialized"
    $isInitialized = false;
    try {
        $db->query("SELECT 1 FROM settings LIMIT 1");
        $isInitialized = true;
    } catch (\PDOException $e) {
        // Table likely doesn't exist
    }

    if (!$isInitialized) {
        include __DIR__ . '/../templates/setup_guide.php';
        exit;
    }

    // Load settings and define core constants
    $settings = $container->get(\App\Services\SettingsService::class);

    if (!defined('BASE_URL')) {
        $baseUrlSetting = $settings->get('base_url');
        $baseUrl = $baseUrlSetting !== null ? (string)$baseUrlSetting : ($config['site']['base_url'] ?? '');
        
        // Server-agnostic normalization: if the web server document root points to the public directory,
        // then '/public' is redundant and shouldn't be prefixed on storefront URLs.
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($baseUrl === '/public' && !str_starts_with($scriptName, '/public')) {
            $baseUrl = '';
        }
        define('BASE_URL', $baseUrl);
    }

    define('SITE_NAME', $settings->get('site_name'));
    define('SITE_NAME_PLAIN', str_replace('|', '', SITE_NAME));

    // Maintenance Mode Check
    if ($settings->get('maintenance_mode') === '1') {
        $path = parse_url($request->getUri(), PHP_URL_PATH);
        
        // Normalize path by stripping BASE_URL (similar to Router logic)
        $relative = $path;
        if (defined('BASE_URL') && BASE_URL !== '' && strpos($relative, BASE_URL) === 0) {
            $baseUrlLen = strlen(BASE_URL);
            $nextChar = substr($relative, $baseUrlLen, 1);
            if ($nextChar === '/' || $nextChar === '' || $nextChar === false) {
                $relative = substr($relative, $baseUrlLen);
                if ($relative === '' || $relative === false) {
                    $relative = '/';
                }
            }
        }
        
        // Remove trailing slash for comparison, but keep root /
        $trimmedPath = rtrim($relative, '/');
        if ($trimmedPath === '') $trimmedPath = '/';

        // Allow admin routes, login, logout and static assets even in maintenance mode
        $isAdminRoute = strpos($trimmedPath, '/admin') === 0;
        $isAuthRoute = in_array($trimmedPath, ['/login', '/logout']);
        $isAsset = strpos($trimmedPath, '/public') === 0 || strpos($trimmedPath, '/css') === 0 || strpos($trimmedPath, '/js') === 0 || strpos($trimmedPath, '/images') === 0 || strpos($trimmedPath, '/favicon.ico') === 0;
        
        if (!$isAdminRoute && !$isAuthRoute && !$isAsset) {
             http_response_code(503);
             $renderer = $container->get(\App\Core\Renderer::class);
             echo $renderer->render('maintenance');
             exit;
        }
    }

} catch (\PDOException $e) {
    $error_message = $e->getMessage();
    include __DIR__ . '/../templates/setup_guide.php';
    exit;
} catch (\Exception $e) {
    // Re-throw other exceptions to be handled by the global exception handler
    throw $e;
}

if (defined('DEBUG_MODE') && DEBUG_MODE) {
    \App\Core\DebugCollector::getInstance()->addMilestone('Bootstrap & DI');
}

$router = new Router($container);

// Load and register routes
$routes = require __DIR__ . '/../config/routes.php';
foreach ($routes as $route) {
    $router->add(
        $route['method'],
        $route['path'],
        $route['handler'],
        $route['middlewares'] ?? []
    );
}

if (defined('DEBUG_MODE') && DEBUG_MODE) {
    \App\Core\DebugCollector::getInstance()->addMilestone('Routing & Middleware');
}

// Handle request
// 1. Server-agnostic static file handler for assets that hit the front controller
$path = parse_url($request->getUri(), PHP_URL_PATH) ?: '';
$filePath = null;
if (strpos($path, '/public/') === 0) {
    $relativePath = substr($path, 7); // Strip '/public' but keep leading slash
    $filePath = __DIR__ . $relativePath;
} elseif (preg_match('#^/(css|js|images|uploads)/#', $path) || in_array($path, ['/favicon.ico', '/apple-touch-icon.png', '/apple-touch-icon-precomposed.png'])) {
    $filePath = __DIR__ . $path;
}

if ($filePath && file_exists($filePath) && is_file($filePath)) {
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimeTypes = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'ico'  => 'image/x-icon',
        'svg'  => 'image/svg+xml',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
    ];
    $contentType = $mimeTypes[strtolower($ext)] ?? 'application/octet-stream';
    header("Content-Type: " . $contentType);
    header("Cache-Control: public, max-age=31536000");
    readfile($filePath);
    exit;
}

// 2. Serve static files via built-in server fallback for non-/public paths
if (php_sapi_name() === 'cli-server') {
    if ($path !== '/') {
        $filePath = __DIR__ . $path;
        if (file_exists($filePath) && is_file($filePath) && $path !== '/index.php') {
            return false;
        }
    }
}

$response = $router->dispatch($request);
$response->send();
