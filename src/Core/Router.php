<?php
namespace App\Core;

class Router {
    private array $routes = [];

    public function __construct(
        private ?Container $container = null,
        private ?\Psr\Log\LoggerInterface $logger = null
    ) {
        if ($this->container && !$this->logger) {
            try {
                $this->logger = $this->container->get(\Psr\Log\LoggerInterface::class);
            } catch (\Exception $e) {
                // Logger not registered or fail
            }
        }
    }

    public function add(string $method, string $path, array $handler, array $middlewares = []) {
        $this->routes[] = compact('method', 'path', 'handler', 'middlewares');
    }

    public function get(string $path, array $handler, array $middlewares = []) {
        $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array $handler, array $middlewares = []) {
        $this->add('POST', $path, $handler, $middlewares);
    }

    public function dispatch(string $uri, string $method) {
        $route = $this->match($uri, $method);
        
        if ($route) {
            // Run middlewares
            foreach ($route['middlewares'] as $middleware) {
                $middlewareInst = $this->container ? $this->container->get($middleware) : new $middleware();
                $middlewareInst->handle();
            }

            $controllerClass = $route['handler'][0];
            $action = $route['handler'][1];
            
            $controller = $this->container ? $this->container->get($controllerClass) : new $controllerClass();
            return call_user_func_array([$controller, $action], $route['params']);
        }

        if ($this->logger) {
            $this->logger->warning('Route not found: {method} {uri}', [
                'method' => $method,
                'uri' => $uri
            ]);
        }

        http_response_code(404);
        
        if ($this->container) {
            try {
                $renderer = $this->container->get(Renderer::class);
                return $renderer->render('404');
            } catch (\Exception $e) {
                // Fallback to basic message if renderer fails
            }
        }

        echo "404 Not Found";
    }

    public function match(string $uri, string $method): ?array {
        $path = parse_url($uri, PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            // Replace dynamic parts like :slug with a regex group
            $pattern = preg_replace('/:[a-zA-Z0-9_]+/', '([a-zA-Z0-9_-]+)', $route['path']);
            if ($route['method'] === $method && preg_match('#^' . $pattern . '/?$#', $path, $matches)) {
                array_shift($matches); // Remove the full path match
                $route['params'] = $matches;
                return $route;
            }
        }
        
        return null;
    }
}
