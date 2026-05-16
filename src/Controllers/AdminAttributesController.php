<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
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

    public function list(Request $request): Response {
        $attributes = $this->attributeService->getAll();
        return new HtmlResponse($this->renderer->adminRender('attributes_list', [
            'page_title' => 'Attributes',
            'active'     => 'attributes',
            'attributes' => $attributes,
            'flash_msg'  => flash('msg'),
        ]));
    }

    public function create(Request $request): Response {
        return new HtmlResponse($this->renderer->adminRender('attributes_form', [
            'page_title' => 'Add Attribute',
            'active'     => 'attributes',
            'is_new'     => true,
            'attribute'  => [],
            'attribute_id' => 0,
            'values'     => [],
            'errors'     => [],
        ]));
    }

    public function edit(Request $request): Response {
        $id = (int)$request->getQuery('id', 0);
        $attribute = $this->attributeService->findById($id);
        if (!$attribute) return new RedirectResponse('/admin/attributes');

        $values = $this->attributeService->getValues($id);

        return new HtmlResponse($this->renderer->adminRender('attributes_form', [
            'page_title' => 'Edit Attribute',
            'active'     => 'attributes',
            'is_new'     => false,
            'attribute'  => $attribute,
            'attribute_id' => $id,
            'values'     => $values,
            'errors'     => [],
        ]));
    }

    public function save(Request $request): Response {
        $post = $request->getPost();
        $id = (int)($post['id'] ?? 0);
        $data = ['name' => trim($post['name'] ?? '')];

        $errors = $this->validator->check($post, ['name' => 'required']);

        if (!$errors) {
            $attributeId = $this->attributeService->save($data, $id);
            $finalId = $id ?: $attributeId;

            // Handle values
            if (isset($post['values']) && is_array($post['values'])) {
                $i = 0;
                foreach ($post['values'] as $v) {
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
            return new RedirectResponse('/admin/attributes');
        }

        return new HtmlResponse($this->renderer->adminRender('attributes_form', [
            'page_title' => ($id ? 'Edit' : 'Add') . ' Attribute',
            'active'     => 'attributes',
            'is_new'     => !$id,
            'attribute'  => $data,
            'attribute_id' => $id,
            'values'     => $id ? $this->attributeService->getValues($id) : [],
            'errors'     => $errors,
        ]));
    }

    public function delete(Request $request): Response {
        $id = (int)$request->getPost('id', 0);
        if ($id) {
            $this->attributeService->delete($id);
            flash('msg', 'Attribute deleted.');
        }
        return new RedirectResponse('/admin/attributes');
    }
}
