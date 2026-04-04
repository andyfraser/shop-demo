<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Core\Validator;
use App\Core\Database;
use App\Services\DeliveryService;
use App\Services\SecurityService;

class AdminDeliveryController {
    public function list() {
        Renderer::adminRender('delivery_list', [
            'page_title' => 'Delivery Options',
            'active'     => 'delivery',
            'options'    => DeliveryService::all(),
        ]);
    }

    public function create() {
        Renderer::adminRender('delivery_form', [
            'page_title' => 'New Delivery Option',
            'active'     => 'delivery',
            'option'     => ['name' => '', 'price' => '', 'active' => 1, 'min_order_total' => 0],
            'errors'     => [],
        ]);
    }

    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        $option = DeliveryService::get($id);
        if (!$option) redirect('/admin/delivery');

        Renderer::adminRender('delivery_form', [
            'page_title' => 'Edit Delivery Option',
            'active'     => 'delivery',
            'option'     => $option,
            'errors'     => [],
        ]);
    }

    public function save() {
        SecurityService::verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'id'              => $id,
            'name'            => trim($_POST['name'] ?? ''),
            'price'           => (float)($_POST['price'] ?? 0),
            'active'          => isset($_POST['active']) ? 1 : 0,
            'min_order_total' => (float)($_POST['min_order_total'] ?? 0),
        ];

        $errors = Validator::check($data, [
            'name'  => 'required',
            'price' => 'required',
        ]);

        if (!$errors) {
            DeliveryService::save($data);
            flash('success', 'Delivery option saved.');
            redirect('/admin/delivery');
        }

        Renderer::adminRender('delivery_form', [
            'page_title' => $id ? 'Edit Delivery Option' : 'New Delivery Option',
            'active'     => 'delivery',
            'option'     => $data,
            'errors'     => $errors,
        ]);
    }

    public function delete() {
        $id = (int)($_GET['id'] ?? 0);
        DeliveryService::delete($id);
        flash('success', 'Delivery option deleted.');
        redirect('/admin/delivery');
    }
}
