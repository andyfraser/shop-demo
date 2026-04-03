<?php
namespace App\Core;

class Router {
    private array $routes = [];

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
        $path = parse_url($uri, PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            // Replace dynamic parts like :slug with a regex group
            $pattern = preg_replace('/:[a-zA-Z0-9_]+/', '([a-zA-Z0-9_-]+)', $route['path']);
            if ($route['method'] === $method && preg_match('#^' . $pattern . '/?$#', $path, $matches)) {
                array_shift($matches); // Remove the full path match
                
                // Run middlewares
                foreach ($route['middlewares'] as $middleware) {
                    $middlewareInst = new $middleware();
                    $middlewareInst->handle();
                }

                $controllerClass = $route['handler'][0];
                $action = $route['handler'][1];
                
                $controller = new $controllerClass();
                return call_user_func_array([$controller, $action], $matches);
            }
        }

        http_response_code(404);
        echo "404 Not Found";
    }
}
