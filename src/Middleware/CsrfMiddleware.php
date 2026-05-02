<?php
namespace App\Middleware;

use App\Services\SecurityServiceInterface;

class CsrfMiddleware {
    public function __construct(private SecurityServiceInterface $security) {}

    public function handle() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->security->verifyCsrf();
        }
    }
}
