<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Renderer;
use App\Core\Validator;
use App\Services\DeliveryServiceInterface;
use App\Services\SecurityServiceInterface;

class AdminDeliveryController {
    public function __construct(
        private Renderer $renderer,
        private Validator $validator,
        private DeliveryServiceInterface $delivery,
        private SecurityServiceInterface $security,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function list(Request $request): Response {
        return new HtmlResponse($this->renderer->adminRender('delivery_list', [
            'page_title' => 'Delivery Options',
            'active'     => 'delivery',
            'options'    => $this->delivery->all(),
        ]));
    }

    public function create(Request $request): Response {
        return new HtmlResponse($this->renderer->adminRender('delivery_form', [
            'page_title' => 'New Delivery Option',
            'active'     => 'delivery',
            'option'     => ['name' => '', 'price' => '', 'active' => 1, 'min_order_total' => 0],
            'errors'     => [],
        ]));
    }

    public function edit(Request $request): Response {
        $id = (int)$request->getQuery('id', 0);
        $option = $this->delivery->get($id);
        if (!$option) return new RedirectResponse('/admin/delivery');

        return new HtmlResponse($this->renderer->adminRender('delivery_form', [
            'page_title' => 'Edit Delivery Option',
            'active'     => 'delivery',
            'option'     => $option,
            'errors'     => [],
        ]));
    }

    public function save(Request $request): Response {
        $post = $request->getPost();
        $id = (int)($post['id'] ?? 0);
        $data = [
            'id'              => $id,
            'name'            => trim($post['name'] ?? ''),
            'price'           => (float)($post['price'] ?? 0),
            'active'          => isset($post['active']) ? 1 : 0,
            'min_order_total' => (float)($post['min_order_total'] ?? 0),
        ];

        $errors = $this->validator->check($data, [
            'name'  => 'required',
            'price' => 'required',
        ]);

        if (!$errors) {
            $this->delivery->save($data);
            $action = $id ? 'updated' : 'created';
            $this->logger->info("Admin {action} delivery option: {name} (ID: {id})", [
                'action' => $action,
                'name' => $data['name'],
                'id' => $id ?: 'new'
            ]);
            flash('success', 'Delivery option saved.');
            return new RedirectResponse('/admin/delivery');
        }

        return new HtmlResponse($this->renderer->adminRender('delivery_form', [
            'page_title' => $id ? 'Edit Delivery Option' : 'New Delivery Option',
            'active'     => 'delivery',
            'option'     => $data,
            'errors'     => $errors,
        ]));
    }

    public function delete(Request $request): Response {
        $id = (int)$request->getPost('id', 0);
        $this->delivery->delete($id);
        $this->logger->info("Admin deleted delivery option ID: {id}", ['id' => $id]);
        flash('success', 'Delivery option deleted.');
        return new RedirectResponse('/admin/delivery');
    }
}
