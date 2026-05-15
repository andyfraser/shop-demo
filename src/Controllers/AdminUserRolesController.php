<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Renderer;
use App\Core\Validator;
use App\Services\UserRoleServiceInterface;
use App\Services\SecurityServiceInterface;

class AdminUserRolesController {
    public function __construct(
        private UserRoleServiceInterface $roleService,
        private Renderer $renderer,
        private Validator $validator,
        private SecurityServiceInterface $security,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function list(Request $request): Response {
        $roles = $this->roleService->getAll();

        return new HtmlResponse($this->renderer->adminRender('user_roles_list', [
            'page_title' => 'User Roles',
            'active'     => 'user-roles',
            'roles'      => $roles,
            'flash_msg'  => flash('msg'),
        ]));
    }

    public function create(Request $request): Response {
        return new HtmlResponse($this->renderer->adminRender('user_roles_form', [
            'page_title' => 'Add User Role',
            'active'     => 'user-roles',
            'is_new'     => true,
            'role'       => [],
            'role_id'    => 0,
            'errors'     => [],
        ]));
    }

    public function edit(Request $request): Response {
        $id = (int)$request->getQuery('id', 0);
        $role = $this->roleService->findById($id);

        if (!$role) {
            return new RedirectResponse('/admin/user-roles');
        }

        return new HtmlResponse($this->renderer->adminRender('user_roles_form', [
            'page_title' => 'Edit User Role',
            'active'     => 'user-roles',
            'is_new'     => false,
            'role'       => $role,
            'role_id'    => $id,
            'errors'     => [],
        ]));
    }

    public function save(Request $request): Response {
        $post = $request->getPost();
        $id = (int)($post['id'] ?? 0);
        $data = [
            'name'        => trim($post['name'] ?? ''),
            'slug'        => trim($post['slug'] ?? ''),
            'description' => trim($post['description'] ?? ''),
        ];

        $errors = $this->validator->check($post, [
            'name' => 'required',
            'slug' => 'required',
        ]);

        if (!$errors) {
            $existing = $this->roleService->findBySlug($data['slug']);
            if ($existing && $existing->id !== $id) {
                $errors[] = 'Slug already in use.';
            }
        }

        if (!$errors) {
            $this->roleService->save($data, $id);
            flash('msg', $id ? 'User role updated.' : 'User role created.');
            return new RedirectResponse('/admin/user-roles');
        }

        return new HtmlResponse($this->renderer->adminRender('user_roles_form', [
            'page_title' => ($id ? 'Edit' : 'Add') . ' User Role',
            'active'     => 'user-roles',
            'is_new'     => !$id,
            'role'       => $data,
            'role_id'    => $id,
            'errors'     => $errors,
        ]));
    }

    public function delete(Request $request): Response {
        $id = (int)$request->getQuery('id', 0);
        if ($id) {
            $role = $this->roleService->findById($id);
            if ($role && in_array($role->slug, ['admin', 'customer'])) {
                flash('err', 'Cannot delete system roles.');
            } else {
                $this->roleService->delete($id);
                flash('msg', 'User role deleted.');
            }
        }
        return new RedirectResponse('/admin/user-roles');
    }
}
