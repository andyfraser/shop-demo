<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Core\Validator;
use App\Services\SecurityServiceInterface;
use App\Services\SettingsServiceInterface;
use App\Services\UserServiceInterface;

class AdminUsersController {
    public function __construct(
        private UserServiceInterface $userService,
        private Renderer $renderer,
        private Validator $validator,
        private SecurityServiceInterface $security,
        private SettingsServiceInterface $settings,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function list() {
        $users = $this->userService->getAll();

        $this->renderer->adminRender('users_list', [
            'page_title' => 'Users',
            'active'     => 'users',
            'users'      => $users,
            'flash_msg'  => flash('msg'),
            'flash_err'  => flash('err'),
        ]);
    }

    public function create() {
        $this->renderer->adminRender('users_form', [
            'page_title'       => 'Add User',
            'active'           => 'users',
            'is_new'           => true,
            'user'             => [],
            'user_id'          => 0,
            'errors'           => [],
            'password_min_len' => (int)$this->settings->get('password_min_length'),
        ]);
    }

    public function edit() {
        $user_id = (int)($_GET['id'] ?? 0);
        $user = $this->userService->findById($user_id);

        if (!$user) {
            redirect('/admin/users');
        }

        $this->renderer->adminRender('users_form', [
            'page_title'       => 'Edit User',
            'active'           => 'users',
            'is_new'           => false,
            'user'             => $user,
            'user_id'          => $user_id,
            'errors'           => [],
            'password_min_len' => (int)$this->settings->get('password_min_length'),
        ]);
    }

    public function save() {
        $this->security->verifyCsrf();
        $user_id = (int)($_POST['id'] ?? 0);
        
        $data = [
            'name'        => trim($_POST['name'] ?? ''),
            'email'       => trim($_POST['email'] ?? ''),
            'role'        => in_array($_POST['role'] ?? '', ['admin', 'customer']) ? $_POST['role'] : 'customer',
            'address'     => trim($_POST['address'] ?? ''),
            'is_verified' => isset($_POST['is_verified']) ? 1 : 0,
        ];
        $pass = $_POST['password'] ?? '';

        $minLen = $this->settings->get('password_min_length');
        $errors = $this->validator->check($_POST, [
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
            redirect('/admin/users');
        }

        $this->renderer->adminRender('users_form', [
            'page_title'       => ($user_id ? 'Edit' : 'Add') . ' User',
            'active'           => 'users',
            'is_new'           => !$user_id,
            'user'             => $data,
            'user_id'          => $user_id,
            'errors'           => $errors,
            'password_min_len' => (int)$this->settings->get('password_min_length'),
        ]);
    }

    public function delete() {
        $user_id = (int)($_GET['id'] ?? 0);
        $current = $this->userService->findById((int)current_user()->id);
        
        if ($user_id === $current->id) {
            flash('err', 'You cannot delete your own account.');
        } else if ($user_id) {
            $this->userService->delete($user_id);
            $this->logger->info("Admin deleted user ID: {id}", ['id' => $user_id]);
            flash('msg', 'User deleted.');
        }
        redirect('/admin/users');
    }
}
