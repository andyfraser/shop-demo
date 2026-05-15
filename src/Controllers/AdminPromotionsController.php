<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Renderer;
use App\Core\Validator;
use App\Services\PromotionServiceInterface;
use App\Services\ProductServiceInterface;
use App\Services\CategoryServiceInterface;
use App\Services\SecurityServiceInterface;
use App\Services\UserRoleServiceInterface;

class AdminPromotionsController {
    public function __construct(
        private PromotionServiceInterface $promotionService,
        private ProductServiceInterface $productService,
        private CategoryServiceInterface $categoryService,
        private UserRoleServiceInterface $roleService,
        private Renderer $renderer,
        private Validator $validator,
        private SecurityServiceInterface $security,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function list(Request $request): Response {
        $promotions = $this->promotionService->getAllForAdmin();

        return new HtmlResponse($this->renderer->adminRender('promotions_list', [
            'page_title' => 'Promotions',
            'active'     => 'promotions',
            'promotions' => $promotions,
            'flash_msg'  => flash('msg'),
        ]));
    }

    public function create(Request $request): Response {
        return new HtmlResponse($this->renderer->adminRender('promotions_form', [
            'page_title' => 'Add Promotion',
            'active'     => 'promotions',
            'is_new'     => true,
            'promotion'  => [],
            'promotion_id' => 0,
            'products'   => $this->productService->getAllForAdmin(new \App\Core\QueryCriteria()),
            'categories' => $this->categoryService->getFlat(),
            'roles'      => $this->roleService->getAll(),
            'errors'     => [],
        ]));
    }

    public function edit(Request $request): Response {
        $id = (int)$request->getQuery('id', 0);
        $promotion = $this->promotionService->findById($id);

        if (!$promotion) {
            return new RedirectResponse('/admin/promotions');
        }

        return new HtmlResponse($this->renderer->adminRender('promotions_form', [
            'page_title' => 'Edit Promotion',
            'active'     => 'promotions',
            'is_new'     => false,
            'promotion'  => $promotion,
            'promotion_id' => $id,
            'products'   => $this->productService->getAllForAdmin(new \App\Core\QueryCriteria()),
            'categories' => $this->categoryService->getFlat(),
            'roles'      => $this->roleService->getAll(),
            'errors'     => [],
        ]));
    }

    public function save(Request $request): Response {
        $post = $request->getPost();
        $id = (int)($post['id'] ?? 0);
        $data = [
            'name'                 => trim($post['name'] ?? ''),
            'description'          => trim($post['description'] ?? ''),
            'code'                 => trim($post['code'] ?? ''),
            'type'                 => $post['type'] ?? '',
            'value'                => (float)($post['value'] ?? 0),
            'buy_qty'              => !empty($post['buy_qty']) ? (int)$post['buy_qty'] : null,
            'get_qty'              => !empty($post['get_qty']) ? (int)$post['get_qty'] : null,
            'target_type'          => $post['target_type'] ?? '',
            'min_order_amount'     => (float)($post['min_order_amount'] ?? 0),
            'start_date'           => !empty($post['start_date']) ? $post['start_date'] : null,
            'end_date'             => !empty($post['end_date']) ? $post['end_date'] : null,
            'usage_limit'          => isset($post['usage_limit']) && $post['usage_limit'] !== '' ? (int)$post['usage_limit'] : null,
            'usage_limit_per_user' => isset($post['usage_limit_per_user']) && $post['usage_limit_per_user'] !== '' ? (int)$post['usage_limit_per_user'] : null,
            'priority'             => (int)($post['priority'] ?? 0),
            'stackable'            => isset($post['stackable']) ? 1 : 0,
            'target_role'          => !empty($post['target_role']) ? $post['target_role'] : null,
            'active'               => isset($post['active']) ? 1 : 0,
            'target_ids'           => $post['target_ids'] ?? [],
            'excluded_ids'         => $post['excluded_ids'] ?? [],
            'additional_codes'     => !empty($post['additional_codes']) ? array_filter(array_map('trim', explode(',', $post['additional_codes']))) : [],
            'tiers'                => []
        ];

        // Process tiers
        if (!empty($post['tier_min']) && is_array($post['tier_min'])) {
            foreach ($post['tier_min'] as $index => $min) {
                if ($min !== '' && isset($post['tier_value'][$index])) {
                    $data['tiers'][] = [
                        'min_amount' => (float)$min,
                        'value' => (float)$post['tier_value'][$index]
                    ];
                }
            }
        }

        $errors = $this->validator->check($post, [
            'name'        => 'required',
            'type'        => 'required',
            'value'       => 'required',
            'target_type' => 'required',
        ]);

        if (!$errors) {
            $this->promotionService->save($data, $id);
            flash('msg', $id ? 'Promotion updated.' : 'Promotion created.');
            return new RedirectResponse('/admin/promotions');
        }

        return new HtmlResponse($this->renderer->adminRender('promotions_form', [
            'page_title' => ($id ? 'Edit' : 'Add') . ' Promotion',
            'active'     => 'promotions',
            'is_new'     => !$id,
            'promotion'  => $data,
            'promotion_id' => $id,
            'products'   => $this->productService->getAllForAdmin(new \App\Core\QueryCriteria()),
            'categories' => $this->categoryService->getFlat(),
            'roles'      => $this->roleService->getAll(),
            'errors'     => $errors,
        ]));
    }

    public function delete(Request $request): Response {
        $id = (int)$request->getQuery('id', 0);
        if ($id) {
            $this->promotionService->delete($id);
            flash('msg', 'Promotion deleted.');
        }
        return new RedirectResponse('/admin/promotions');
    }
}
