<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\JsonResponse;

class CorsMiddleware {
    private array $allowedOrigins;

    public function __construct() {
        $config = [];
        if (file_exists(__DIR__ . '/../../config/config.php')) {
            $config = require __DIR__ . '/../../config/config.php';
        }
        $this->allowedOrigins = $config['api']['allowed_cors_origins'] ?? ['*'];
    }

    public function handle(Request $request): ?Response {
        $method = $request->getMethod();
        
        // Handle CORS Preflight request
        if ($method === 'OPTIONS') {
            $response = new JsonResponse(null, 204);
            $this->addCorsHeaders($request, $response);
            return $response;
        }

        return null;
    }

    public function addCorsHeaders(Request $request, Response $response): void {
        $origin = $request->getServer('HTTP_ORIGIN') ?? '';
        
        if (in_array('*', $this->allowedOrigins)) {
            $response->setHeader('Access-Control-Allow-Origin', '*');
        } elseif (in_array($origin, $this->allowedOrigins)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        } else {
            // Default to the first origin or empty if origin is not allowed
            $response->setHeader('Access-Control-Allow-Origin', !empty($this->allowedOrigins) ? $this->allowedOrigins[0] : '');
        }

        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Cart-UUID');
        $response->setHeader('Access-Control-Max-Age', '86400');
    }
}
