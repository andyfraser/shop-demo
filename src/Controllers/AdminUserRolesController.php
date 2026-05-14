<?php

namespace App\Controllers;

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

    public function list() {
        $roles = $this->roleService->getAll();

        $this->renderer->adminRender('user_roles_list', [
            'page_title' => 'User Roles',
            'active'     => 'user-roles',
            'roles'      => $roles,
            'flash_msg'  => flash('msg'),
        ]);
    }

    public function create() {
        $this->renderer->adminRender('user_roles_form', [
            'page_title' => 'Add User Role',
            'active'     => 'user-roles',
            'is_new'     => true,
            'role'       => [],
            'role_id'    => 0,
            'errors'     => [],
        ]);
    }

    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        $role = $this->roleService->findById($id);

        if (!$role) {
            redirect('/admin/user-roles');
        }

        $this->renderer->adminRender('user_roles_form', [
            'page_title' => 'Edit User Role',
            'active'     => 'user-roles',
            'is_new'     => false,
            'role'       => $role,
            'role_id'    => $id,
            'errors'     => [],
        ]);
    }

    public function save() {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'name'        => trim($_POST['name'] ?? ''),
            'slug'        => trim($_POST['slug'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
        ];

        $errors = $this->validator->check($_POST, [
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
            redirect('/admin/user-roles');
        }

        $this->renderer->adminRender('user_roles_form', [
            'page_title' => ($id ? 'Edit' : 'Add') . ' User Role',
            'active'     => 'user-roles',
            'is_new'     => !$id,
            'role'       => $data,
            'role_id'    => $id,
            'errors'     => $errors,
        ]);
    }

    public function delete() {
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $role = $this->roleService->findById($id);
            if ($role && in_array($role->slug, ['admin', 'customer'])) {
                flash('err', 'Cannot delete system roles.');
            } else {
                $this->roleService->delete($id);
                flash('msg', 'User role deleted.');
            }
        }
        redirect('/admin/user-roles');
    }
}
