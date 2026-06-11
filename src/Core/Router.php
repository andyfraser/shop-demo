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

    public function dispatch(Request $request): Response {
        $uri = $request->getUri();
        $method = $request->getMethod();
        $path = parse_url($uri, PHP_URL_PATH) ?: '';
        $isApi = str_starts_with($path, '/api/') || $path === '/api';
        
        $cors = null;
        if ($isApi) {
            $cors = $this->container ? $this->container->get(\App\Middleware\CorsMiddleware::class) : new \App\Middleware\CorsMiddleware();
            if ($method === 'OPTIONS') {
                return $cors->handle($request);
            }
        }

        $route = $this->match($uri, $method);
        
        if ($route) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                \App\Core\DebugCollector::getInstance()->setMatchedRoute($route);
            }
            // Run middlewares
            foreach ($route['middlewares'] as $middleware) {
                $middlewareInst = $this->container ? $this->container->get($middleware) : new $middleware();
                $response = $middlewareInst->handle($request);
                if ($response instanceof Response) {
                    if ($isApi && $cors) {
                        $cors->addCorsHeaders($request, $response);
                    }
                    return $response;
                }
            }

            $controllerClass = $route['handler'][0];
            $action = $route['handler'][1];
            
            $controller = $this->container ? $this->container->get($controllerClass) : new $controllerClass();
            
            // Pass $request as the first argument, followed by route params
            $params = array_merge([$request], $route['params'] ?? []);
            $response = call_user_func_array([$controller, $action], $params);
            
            if ($response instanceof Response) {
                if ($isApi && $cors) {
                    $cors->addCorsHeaders($request, $response);
                }
                return $response;
            }
            
            // Fallback if the controller doesn't return a Response (during transition)
            $response = new \App\Core\Responses\HtmlResponse((string)$response);
            if ($isApi && $cors) {
                $cors->addCorsHeaders($request, $response);
            }
            return $response;
        }

        // Avoid warning logging for common static assets to prevent log pollution
        $isAsset = strpos($path, '/public/') === 0 
            || strpos($path, '/css/') === 0 
            || strpos($path, '/js/') === 0 
            || strpos($path, '/images/') === 0 
            || strpos($path, '/uploads/') === 0 
            || strpos($path, '/favicon.ico') === 0 
            || strpos($path, '/apple-touch-icon') === 0 
            || preg_match('#\.(css|js|png|jpg|jpeg|gif|ico|svg|woff2?|map)$#i', $path);

        if ($this->logger && !$isAsset) {
            $this->logger->warning('Route not found: {method} {uri}', [
                'method' => $method,
                'uri' => $uri
            ]);
        }
        
        if ($isApi) {
            $response = new \App\Core\Responses\JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'API route not found'
                ]
            ], 404);
            if ($cors) {
                $cors->addCorsHeaders($request, $response);
            }
            return $response;
        }

        if ($this->container) {
            try {
                $renderer = $this->container->get(Renderer::class);
                return new \App\Core\Responses\HtmlResponse($renderer->render('404'), 404);
            } catch (\Exception $e) {
                // Fallback
            }
        }

        return new \App\Core\Responses\HtmlResponse("404 Not Found", 404);
    }

    public function match(string $uri, string $method): ?array {
        $path = parse_url($uri, PHP_URL_PATH);
        
        // Strip BASE_URL from the beginning of the path if it exists
        if (defined('BASE_URL') && BASE_URL !== '' && strpos($path, BASE_URL) === 0) {
            $baseUrlLen = strlen(BASE_URL);
            $nextChar = substr($path, $baseUrlLen, 1);
            if ($nextChar === '/' || $nextChar === '' || $nextChar === false) {
                $path = substr($path, $baseUrlLen);
                if ($path === '' || $path === false) {
                    $path = '/';
                }
            }
        }
        
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
