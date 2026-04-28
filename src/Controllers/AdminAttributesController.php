<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Core\Validator;
use App\Services\AttributeServiceInterface;
use App\Services\SecurityServiceInterface;

class AdminAttributesController {
    public function __construct(
        private AttributeServiceInterface $attributeService,
        private Renderer $renderer,
        private Validator $validator,
        private SecurityServiceInterface $security,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function list() {
        $attributes = $this->attributeService->getAll();
        $this->renderer->adminRender('attributes_list', [
            'page_title' => 'Attributes',
            'active'     => 'attributes',
            'attributes' => $attributes,
            'flash_msg'  => flash('msg'),
        ]);
    }

    public function create() {
        $this->renderer->adminRender('attributes_form', [
            'page_title' => 'Add Attribute',
            'active'     => 'attributes',
            'is_new'     => true,
            'attribute'  => [],
            'attribute_id' => 0,
            'values'     => [],
            'errors'     => [],
        ]);
    }

    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        $attribute = $this->attributeService->findById($id);
        if (!$attribute) redirect('/admin/attributes');

        $values = $this->attributeService->getValues($id);

        $this->renderer->adminRender('attributes_form', [
            'page_title' => 'Edit Attribute',
            'active'     => 'attributes',
            'is_new'     => false,
            'attribute'  => $attribute,
            'attribute_id' => $id,
            'values'     => $values,
            'errors'     => [],
        ]);
    }

    public function save() {
        $this->security->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $data = ['name' => trim($_POST['name'] ?? '')];

        $errors = $this->validator->check($_POST, ['name' => 'required']);

        if (!$errors) {
            $attributeId = $this->attributeService->save($data, $id);
            $finalId = $id ?: $attributeId;

            // Handle values
            if (isset($_POST['values']) && is_array($_POST['values'])) {
                $i = 0;
                foreach ($_POST['values'] as $v) {
                    if (!empty($v['delete']) && !empty($v['id'])) {
                        $this->attributeService->deleteValue((int)$v['id']);
                        continue;
                    }
                    if (empty($v['value'])) continue;

                    $vData = [
                        'attribute_id' => $finalId,
                        'value'        => trim($v['value']),
                        'sort_order'   => $i++
                    ];
                    $vId = !empty($v['id']) ? (int)$v['id'] : 0;
                    $this->attributeService->saveValue($vData, $vId);
                }
            }

            flash('msg', 'Attribute saved.');
            redirect('/admin/attributes');
        }

        $this->renderer->adminRender('attributes_form', [
            'page_title' => ($id ? 'Edit' : 'Add') . ' Attribute',
            'active'     => 'attributes',
            'is_new'     => !$id,
            'attribute'  => $data,
            'attribute_id' => $id,
            'values'     => $id ? $this->attributeService->getValues($id) : [],
            'errors'     => $errors,
        ]);
    }

    public function delete() {
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $this->attributeService->delete($id);
            flash('msg', 'Attribute deleted.');
        }
        redirect('/admin/attributes');
    }
}
