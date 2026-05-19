<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Renderer;
use App\Core\Validator;
use App\Repositories\CurrencyRepositoryInterface;
use App\Services\CurrencyServiceInterface;
use Psr\Log\LoggerInterface;

class AdminCurrencyController {
    public function __construct(
        private CurrencyRepositoryInterface $repository,
        private CurrencyServiceInterface $service,
        private Renderer $renderer,
        private Validator $validator,
        private LoggerInterface $logger
    ) {}

    public function list(Request $request): Response {
        $all = $this->repository->getAll();

        return new HtmlResponse($this->renderer->adminRender('currencies_list', [
            'page_title' => 'Currencies',
            'active'     => 'currencies',
            'currencies' => $all,
            'flash_msg'  => flash('msg'),
        ]));
    }

    public function create(Request $request): Response {
        return new HtmlResponse($this->renderer->adminRender('currencies_form', [
            'page_title' => 'Add Currency',
            'active'     => 'currencies',
            'is_new'     => true,
            'currency'   => null,
            'errors'     => [],
        ]));
    }

    public function edit(Request $request): Response {
        $id = (int)$request->getQuery('id', 0);
        $currency = $this->repository->findById($id);

        if (!$currency) {
            flash('error', 'Currency not found.');
            return new RedirectResponse('/admin/currencies');
        }

        return new HtmlResponse($this->renderer->adminRender('currencies_form', [
            'page_title' => 'Edit Currency',
            'active'     => 'currencies',
            'is_new'     => false,
            'currency'   => $currency,
            'errors'     => [],
        ]));
    }

    public function save(Request $request): Response {
        $post = $request->getPost();
        $id = (int)($post['id'] ?? 0);

        $errors = $this->validator->check($post, [
            'code'   => 'required',
            'name'   => 'required',
            'symbol' => 'required',
            'exchange_rate' => 'required|positive',
        ]);

        if (!$errors) {
            $data = [
                'code'   => strtoupper(trim($post['code'])),
                'name'   => trim($post['name']),
                'symbol' => trim($post['symbol']),
                'exchange_rate' => (float)$post['exchange_rate'],
                'is_base' => isset($post['is_base']) ? 1 : 0,
                'active'  => isset($post['active']) ? 1 : 0,
            ];

            $this->repository->save($data, $id);
            $this->service->clearCache();
            
            $this->logger->info("Admin saved currency: {code}", ['code' => $data['code']]);
            flash('msg', 'Currency saved successfully.');
            return new RedirectResponse('/admin/currencies');
        }

        return new HtmlResponse($this->renderer->adminRender('currencies_form', [
            'page_title' => ($id ? 'Edit' : 'Add') . ' Currency',
            'active'     => 'currencies',
            'is_new'     => !$id,
            'currency'   => $post,
            'errors'     => $errors,
        ]));
    }
}
