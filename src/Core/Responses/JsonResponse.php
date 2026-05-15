<?php
namespace App\Core\Responses;

use App\Core\Response;

class JsonResponse extends Response {
    public function __construct(mixed $data, int $statusCode = 200, array $headers = []) {
        $content = json_encode($data);
        parent::__construct($content, $statusCode, $headers);
        $this->setHeader('Content-Type', 'application/json');
    }
}
