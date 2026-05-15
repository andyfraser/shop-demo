<?php
namespace App\Core\Responses;

use App\Core\Response;

class HtmlResponse extends Response {
    public function __construct(string $content, int $statusCode = 200, array $headers = []) {
        parent::__construct($content, $statusCode, $headers);
        $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}
