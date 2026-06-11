<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\JsonResponse;

class ApiController {
    public function ping(Request $request): Response {
        return new JsonResponse([
            'success' => true,
            'data' => [
                'ping' => 'pong',
                'timestamp' => time(),
                'method' => $request->getMethod()
            ]
        ]);
    }
}
