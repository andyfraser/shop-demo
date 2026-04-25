<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Renderer;
use App\Core\Validator;
use App\Services\SecurityService;
use App\Services\SettingsService;

class AdminUsersController {
    public function __construct(
        private \PDO $db,
        private Renderer $renderer,
        private Validator $validator,
        private SecurityService $security,
        private SettingsService $settings
    ) {}

    public function list() {
        $users = $this->db->query(
            "SELECT u.*, COUNT(o.id) as order_count
             FROM users u
             LEFT JOIN orders o ON o.user_id = u.id
             GROUP BY u.id
             ORDER BY u.id DESC"
        )->fetchAll();

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

        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch() ?: [];

        $this->renderer->adminRender('users_form', [
            'page_title'       => 'Edit User',
            'active'           => 'users',
            'is_new'           => !$user_id,
            'user'             => $user,
            'user_id'          => $user_id,
            'errors'           => [],
            'password_min_len' => (int)$this->settings->get('password_min_length'),
        ]);
    }

    public function save() {
        $this->security->verifyCsrf();
        $user_id = (int)($_POST['id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $role    = in_array($_POST['role'] ?? '', ['admin', 'customer']) ? $_POST['role'] : 'customer';
        $address = trim($_POST['address'] ?? '');
        $pass    = $_POST['password'] ?? '';

        $minLen = $this->settings->get('password_min_length');
        $errors = $this->validator->check($_POST, [
            'name'     => 'required',
            'email'    => 'required|email',
            'password' => $user_id ? "min_length:$minLen" : "required|min_length:$minLen",
        ]);

        if (!$errors) {
            $check = $user_id
                ? $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ?")
                : $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $user_id ? $check->execute([$email, $user_id]) : $check->execute([$email]);

            if ($check->fetch()) {
                $errors[] = 'Email already in use.';
            }
        }

        if (!$errors) {
            if ($user_id) {
                if ($pass) {
                    $this->db->prepare("UPDATE users SET name=?, email=?, role=?, address=?, password_hash=? WHERE id=?")
                       ->execute([$name, $email, $role, $address, password_hash($pass, PASSWORD_DEFAULT), $user_id]);
                } else {
                    $this->db->prepare("UPDATE users SET name=?, email=?, role=?, address=? WHERE id=?")
                       ->execute([$name, $email, $role, $address, $user_id]);
                }
                flash('msg', 'User updated.');
            } else {
                $this->db->prepare("INSERT INTO users (name, email, password_hash, role, address) VALUES (?, ?, ?, ?, ?)")
                   ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $address]);
                flash('msg', 'User created.');
            }
            redirect('/admin/users');
        }

        $this->renderer->adminRender('users_form', [
            'page_title'       => ($user_id ? 'Edit' : 'Add') . ' User',
            'active'           => 'users',
            'is_new'           => !$user_id,
            'user'             => compact('name', 'email', 'role', 'address'),
            'user_id'          => $user_id,
            'errors'           => $errors,
            'password_min_len' => (int)$this->settings->get('password_min_length'),
        ]);
    }

    public function delete() {
        $user_id = (int)($_GET['id'] ?? 0);
        if ($user_id === (int)current_user()['id']) {
            flash('err', 'You cannot delete your own account.');
        } else if ($user_id) {
            $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
            flash('msg', 'User deleted.');
        }
        redirect('/admin/users');
    }
}
