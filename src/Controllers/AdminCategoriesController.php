<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Renderer;
use App\Core\Validator;
use App\Services\CategoryServiceInterface;
use App\Services\SecurityServiceInterface;

class AdminCategoriesController {
    public function __construct(
        private CategoryServiceInterface $categoryService,
        private Renderer $renderer,
        private Validator $validator,
        private SecurityServiceInterface $security,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function list(Request $request): Response {
        $categories = $this->categoryService->getAllForAdmin();

        return new HtmlResponse($this->renderer->adminRender('categories_list', [
            'page_title' => 'Categories',
            'active'     => 'categories',
            'categories' => $categories,
            'flash_msg'  => flash('msg'),
        ]));
    }

    public function create(Request $request): Response {
        return new HtmlResponse($this->renderer->adminRender('categories_form', [
            'page_title'     => 'Add Category',
            'active'         => 'categories',
            'is_new'         => true,
            'category'       => [],
            'category_id'    => 0,
            'all_categories' => $this->categoryService->getFlat(),
            'errors'         => [],
        ]));
    }

    public function edit(Request $request): Response {
        $id = (int)$request->getQuery('id', 0);
        $category = $this->categoryService->findById($id);

        if (!$category) {
            return new RedirectResponse('/admin/categories');
        }

        return new HtmlResponse($this->renderer->adminRender('categories_form', [
            'page_title'     => 'Edit Category',
            'active'         => 'categories',
            'is_new'         => false,
            'category'       => $category,
            'category_id'    => $id,
            'all_categories' => $this->categoryService->getFlat(),
            'errors'         => [],
        ]));
    }

    public function save(Request $request): Response {
        $post = $request->getPost();
        $category_id = (int)($post['id'] ?? 0);
        
        $data = [
            'name'        => trim($post['name'] ?? ''),
            'parent_id'   => !empty($post['parent_id']) ? (int)$post['parent_id'] : null,
            'description' => trim($post['description'] ?? ''),
            'icon'        => trim($post['icon'] ?? ''),
        ];

        $errors = $this->validator->check($post, [
            'name' => 'required',
        ]);
        if ($data['parent_id'] && $data['parent_id'] === $category_id) {
            $errors[] = 'A category cannot be its own parent.';
        }

        if (!$errors) {
            $saved_id = $this->categoryService->save($data, $category_id);

            if ($category_id) {
                $this->logger->info("Admin updated category: {name} (ID: {id})", ['name' => $data['name'], 'id' => $category_id]);
                flash('msg', 'Category updated.');
            } else {
                $this->logger->info("Admin created category: {name} (ID: {id})", ['name' => $data['name'], 'id' => $saved_id]);
                flash('msg', 'Category created.');
            }
            return new RedirectResponse('/admin/categories');
        }

        return new HtmlResponse($this->renderer->adminRender('categories_form', [
            'page_title'     => ($category_id ? 'Edit' : 'Add') . ' Category',
            'active'         => 'categories',
            'is_new'         => !$category_id,
            'category'       => $data,
            'category_id'    => $category_id,
            'all_categories' => $this->categoryService->getFlat(),
            'errors'         => $errors,
        ]));
    }

    public function delete(Request $request): Response {
        $category_id = (int)$request->getQuery('id', 0);
        if ($category_id) {
            $this->categoryService->delete($category_id);
            $this->logger->info("Admin deleted category ID: {id}", ['id' => $category_id]);
            flash('msg', 'Category deleted.');
        }
        return new RedirectResponse('/admin/categories');
    }
}
