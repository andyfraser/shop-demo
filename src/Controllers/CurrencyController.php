<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\RedirectResponse;
use App\Services\CurrencyServiceInterface;

class CurrencyController {
    public function __construct(
        private CurrencyServiceInterface $currencyService
    ) {}

    public function switch(Request $request): Response {
        $code = $request->getPost('currency_code');
        if ($code) {
            $this->currencyService->setCurrentCurrency($code);
        }

        $returnUrl = $_SERVER['HTTP_REFERER'] ?? '/';
        return new RedirectResponse($returnUrl);
    }
}
