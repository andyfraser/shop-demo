<?php

namespace App\Controllers;

use App\Core\Renderer;
use App\Core\Validator;
use App\Services\PromotionServiceInterface;
use App\Services\ProductServiceInterface;
use App\Services\CategoryServiceInterface;
use App\Services\SecurityServiceInterface;

class AdminPromotionsController {
    public function __construct(
        private PromotionServiceInterface $promotionService,
        private ProductServiceInterface $productService,
        private CategoryServiceInterface $categoryService,
        private Renderer $renderer,
        private Validator $validator,
        private SecurityServiceInterface $security,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function list() {
        $promotions = $this->promotionService->getAllForAdmin();

        $this->renderer->adminRender('promotions_list', [
            'page_title' => 'Promotions',
            'active'     => 'promotions',
            'promotions' => $promotions,
            'flash_msg'  => flash('msg'),
        ]);
    }

    public function create() {
        $this->renderer->adminRender('promotions_form', [
            'page_title' => 'Add Promotion',
            'active'     => 'promotions',
            'is_new'     => true,
            'promotion'  => [],
            'promotion_id' => 0,
            'products'   => $this->productService->getAllForAdmin(),
            'categories' => $this->categoryService->getFlat(),
            'errors'     => [],
        ]);
    }

    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        $promotion = $this->promotionService->findById($id);

        if (!$promotion) {
            redirect('/admin/promotions');
        }

        $this->renderer->adminRender('promotions_form', [
            'page_title' => 'Edit Promotion',
            'active'     => 'promotions',
            'is_new'     => false,
            'promotion'  => $promotion,
            'promotion_id' => $id,
            'products'   => $this->productService->getAllForAdmin(),
            'categories' => $this->categoryService->getFlat(),
            'errors'     => [],
        ]);
    }

    public function save() {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'name'             => trim($_POST['name'] ?? ''),
            'description'      => trim($_POST['description'] ?? ''),
            'code'             => trim($_POST['code'] ?? ''),
            'type'             => $_POST['type'] ?? '',
            'value'            => (float)($_POST['value'] ?? 0),
            'buy_qty'          => !empty($_POST['buy_qty']) ? (int)$_POST['buy_qty'] : null,
            'get_qty'          => !empty($_POST['get_qty']) ? (int)$_POST['get_qty'] : null,
            'target_type'      => $_POST['target_type'] ?? '',
            'min_order_amount' => (float)($_POST['min_order_amount'] ?? 0),
            'start_date'       => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'end_date'         => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'usage_limit'      => isset($_POST['usage_limit']) && $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : null,
            'active'           => isset($_POST['active']) ? 1 : 0,
            'target_ids'       => $_POST['target_ids'] ?? []
        ];

        $errors = $this->validator->check($_POST, [
            'name'        => 'required',
            'type'        => 'required',
            'value'       => 'required',
            'target_type' => 'required',
        ]);

        if (!$errors) {
            $this->promotionService->save($data, $id);
            flash('msg', $id ? 'Promotion updated.' : 'Promotion created.');
            redirect('/admin/promotions');
        }

        $this->renderer->adminRender('promotions_form', [
            'page_title' => ($id ? 'Edit' : 'Add') . ' Promotion',
            'active'     => 'promotions',
            'is_new'     => !$id,
            'promotion'  => $data,
            'promotion_id' => $id,
            'products'   => $this->productService->getAllForAdmin(),
            'categories' => $this->categoryService->getFlat(),
            'errors'     => $errors,
        ]);
    }

    public function delete() {
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $this->promotionService->delete($id);
            flash('msg', 'Promotion deleted.');
        }
        redirect('/admin/promotions');
    }
}
