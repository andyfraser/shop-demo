<?php
namespace App\Core\Responses;

use App\Core\Response;

class RedirectResponse extends Response {
    public function __construct(string $url, int $statusCode = 302, array $headers = []) {
        parent::__construct('', $statusCode, $headers);
        $this->setHeader('Location', $url);
    }
}
