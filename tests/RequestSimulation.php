<?php

namespace Tests;

use App\Core\Container;
use App\Core\Request;
use App\Core\Router;
use App\Core\Response;
use App\Core\Renderer;

trait RequestSimulation {
    protected ?Container $container = null;
    protected ?Router $router = null;

    protected function setupApp(): void {
        $this->container = new Container();
        
        $config = require __DIR__ . '/../config/config.example.php';
        // Use the test DB config defined in run.php
        if (defined('DB_CONFIG')) {
            $config['db'] = DB_CONFIG;
        }

        // Mock a default request so services depending on it can be instantiated
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'], [], [], []);
        $this->container->set(Request::class, fn() => $request);

        $servicesRegistrar = require __DIR__ . '/../config/services.php';
        $services = $servicesRegistrar($config);

        foreach ($services as $interface => $resolver) {
            $this->container->set($interface, $resolver);
        }

        // Use NullCache to avoid stale cache leaking between tests in integration simulations
        $this->container->set(\App\Core\Cache\CacheInterface::class, fn() => new \Tests\NullCache());

        // Load settings and define core constants
        $settings = $this->container->get(\App\Services\SettingsService::class);

        if (!defined('BASE_URL')) {
            $baseUrlSetting = $settings->get('base_url');
            $baseUrl = $baseUrlSetting !== null ? (string)$baseUrlSetting : ($config['site']['base_url'] ?? '');
            
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            if ($baseUrl === '/public' && !str_starts_with($scriptName, '/public')) {
                $baseUrl = '';
            }
            define('BASE_URL', $baseUrl);
        }

        if (!defined('SITE_NAME')) {
            define('SITE_NAME', $settings->get('site_name') ?? 'Demoshop');
        }
        if (!defined('SITE_NAME_PLAIN')) {
            define('SITE_NAME_PLAIN', str_replace('|', '', SITE_NAME));
        }

        $this->router = $this->container->get(Router::class);
        
        // Load routes
        $routes = require __DIR__ . '/../config/routes.php';
        foreach ($routes as $route) {
            $this->router->add(
                $route['method'],
                $route['path'],
                $route['handler'],
                $route['middlewares'] ?? []
            );
        }
    }

    protected function simulateRequest(
        string $method,
        string $uri,
        array $post = [],
        array $session = [],
        array $cookies = [],
        array $query = [],
        array $server = []
    ): Response {
        $this->setupApp();

        $server = array_merge([
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $uri,
            'HTTP_HOST' => 'localhost',
            'REMOTE_ADDR' => '127.0.0.1',
        ], $server);

        $request = new Request($query, $post, $server, [], $cookies, $session);
        
        // Sync global $_SESSION for services that use it directly
        $_SESSION = $session;

        // Override the request instance in the container for this specific simulation
        $this->container->set(Request::class, fn() => $request);

        return $this->router->dispatch($request);
    }

    protected function get(string $uri, array $session = [], array $cookies = [], array $query = []): Response {
        return $this->simulateRequest('GET', $uri, [], $session, $cookies, $query);
    }

    protected function post(string $uri, array $post = [], array $session = [], array $cookies = []): Response {
        return $this->simulateRequest('POST', $uri, $post, $session, $cookies);
    }
}
