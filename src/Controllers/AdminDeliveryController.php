<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Core\Validator;
use App\Services\DeliveryService;
use App\Services\SecurityService;

class AdminDeliveryController {
    public function __construct(
        private Renderer $renderer,
        private Validator $validator,
        private DeliveryService $delivery,
        private SecurityService $security
    ) {}

    public function list() {
        $this->renderer->adminRender('delivery_list', [
            'page_title' => 'Delivery Options',
            'active'     => 'delivery',
            'options'    => $this->delivery->all(),
        ]);
    }

    public function create() {
        $this->renderer->adminRender('delivery_form', [
            'page_title' => 'New Delivery Option',
            'active'     => 'delivery',
            'option'     => ['name' => '', 'price' => '', 'active' => 1, 'min_order_total' => 0],
            'errors'     => [],
        ]);
    }

    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        $option = $this->delivery->get($id);
        if (!$option) redirect('/admin/delivery');

        $this->renderer->adminRender('delivery_form', [
            'page_title' => 'Edit Delivery Option',
            'active'     => 'delivery',
            'option'     => $option,
            'errors'     => [],
        ]);
    }

    public function save() {
        $this->security->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'id'              => $id,
            'name'            => trim($_POST['name'] ?? ''),
            'price'           => (float)($_POST['price'] ?? 0),
            'active'          => isset($_POST['active']) ? 1 : 0,
            'min_order_total' => (float)($_POST['min_order_total'] ?? 0),
        ];

        $errors = $this->validator->check($data, [
            'name'  => 'required',
            'price' => 'required',
        ]);

        if (!$errors) {
            $this->delivery->save($data);
            flash('success', 'Delivery option saved.');
            redirect('/admin/delivery');
        }

        $this->renderer->adminRender('delivery_form', [
            'page_title' => $id ? 'Edit Delivery Option' : 'New Delivery Option',
            'active'     => 'delivery',
            'option'     => $data,
            'errors'     => $errors,
        ]);
    }

    public function delete() {
        $id = (int)($_GET['id'] ?? 0);
        $this->delivery->delete($id);
        flash('success', 'Delivery option deleted.');
        redirect('/admin/delivery');
    }
}
