<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Renderer;
use App\Core\Validator;
use App\Services\SecurityServiceInterface;
use App\Services\SettingsServiceInterface;
use App\Services\UserServiceInterface;
use App\Services\UserRoleServiceInterface;

class AdminUsersController {
    public function __construct(
        private UserServiceInterface $userService,
        private UserRoleServiceInterface $roleService,
        private Renderer $renderer,
        private Validator $validator,
        private SecurityServiceInterface $security,
        private SettingsServiceInterface $settings,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function list(Request $request): Response {
        $users = $this->userService->getAll();

        return new HtmlResponse($this->renderer->adminRender('users_list', [
            'page_title' => 'Users',
            'active'     => 'users',
            'users'      => $users,
            'flash_msg'  => flash('msg'),
            'flash_err'  => flash('err'),
        ]));
    }

    public function create(Request $request): Response {
        return new HtmlResponse($this->renderer->adminRender('users_form', [
            'page_title'       => 'Add User',
            'active'           => 'users',
            'is_new'           => true,
            'user'             => [],
            'user_id'          => 0,
            'errors'           => [],
            'roles'            => $this->roleService->getAll(),
            'password_min_len' => (int)$this->settings->get('password_min_length'),
        ]));
    }

    public function edit(Request $request): Response {
        $user_id = (int)$request->getQuery('id', 0);
        $user = $this->userService->findById($user_id);

        if (!$user) {
            return new RedirectResponse('/admin/users');
        }

        return new HtmlResponse($this->renderer->adminRender('users_form', [
            'page_title'       => 'Edit User',
            'active'           => 'users',
            'is_new'           => false,
            'user'             => $user,
            'user_id'          => $user_id,
            'errors'           => [],
            'roles'            => $this->roleService->getAll(),
            'password_min_len' => (int)$this->settings->get('password_min_length'),
        ]));
    }

    public function save(Request $request): Response {
        $post = $request->getPost();
        $user_id = (int)($post['id'] ?? 0);
        
        $roleSlugs = array_map(fn($r) => $r->slug, $this->roleService->getAll());
        $roleSlugs[] = 'admin';
        
        $data = [
            'name'        => trim($post['name'] ?? ''),
            'email'       => trim($post['email'] ?? ''),
            'role'        => in_array($post['role'] ?? '', $roleSlugs) ? $post['role'] : 'customer',
            'address'     => trim($post['address'] ?? ''),
            'is_verified' => isset($post['is_verified']) ? 1 : 0,
        ];
        $pass = $post['password'] ?? '';

        $minLen = $this->settings->get('password_min_length');
        $errors = $this->validator->check($post, [
            'name'     => 'required',
            'email'    => 'required|email',
            'password' => $user_id ? "min_length:$minLen" : "required|min_length:$minLen",
        ]);

        if (!$errors) {
            $existing = $this->userService->findByEmail($data['email']);
            if ($existing && $existing->id !== $user_id) {
                $errors[] = 'Email already in use.';
            }
        }

        if (!$errors) {
            if ($user_id) {
                $user = $this->userService->findById($user_id);
                $data['password_hash'] = $user->password_hash;
                $data['verification_token'] = $user->verification_token;
                if ($pass) {
                    $data['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
                }
            } else {
                $data['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
                $data['verification_token'] = null;
            }

            $saved_id = $this->userService->save($data, $user_id);

            if ($user_id) {
                $this->logger->info("Admin updated user: {email} (ID: {id})", ['email' => $data['email'], 'id' => $user_id]);
                flash('msg', 'User updated.');
            } else {
                $this->logger->info("Admin created user: {email} (ID: {id})", ['email' => $data['email'], 'id' => $saved_id]);
                flash('msg', 'User created.');
            }
            return new RedirectResponse('/admin/users');
        }

        return new HtmlResponse($this->renderer->adminRender('users_form', [
            'page_title'       => ($user_id ? 'Edit' : 'Add') . ' User',
            'active'           => 'users',
            'is_new'           => !$user_id,
            'user'             => $data,
            'user_id'          => $user_id,
            'errors'           => $errors,
            'roles'            => $this->roleService->getAll(),
            'password_min_len' => (int)$this->settings->get('password_min_length'),
        ]));
    }

    public function delete(Request $request): Response {
        $user_id = (int)$request->getPost('id', 0);
        $current = $this->userService->findById((int)current_user()->id);
        
        if ($user_id === $current->id) {
            flash('err', 'You cannot delete your own account.');
        } else if ($user_id) {
            $this->userService->delete($user_id);
            $this->logger->info("Admin deleted user ID: {id}", ['id' => $user_id]);
            flash('msg', 'User deleted.');
        }
        return new RedirectResponse('/admin/users');
    }
}
