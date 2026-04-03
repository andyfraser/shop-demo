<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Renderer;
use App\Services\SecurityService;

class AdminUsersController {
    public function list() {
        $db = Database::getConnection();
        $users = $db->query(
            "SELECT u.*, COUNT(o.id) as order_count
             FROM users u
             LEFT JOIN orders o ON o.user_id = u.id
             GROUP BY u.id
             ORDER BY u.id DESC"
        )->fetchAll();

        Renderer::adminRender('users_list', [
            'page_title' => 'Users',
            'active'     => 'users',
            'users'      => $users,
            'flash_msg'  => flash('msg'),
            'flash_err'  => flash('err'),
        ]);
    }

    public function create() {
        Renderer::adminRender('users_form', [
            'page_title' => 'Add User',
            'active'     => 'users',
            'is_new'     => true,
            'user'       => [],
            'user_id'    => 0,
            'errors'     => [],
        ]);
    }

    public function edit() {
        $db = Database::getConnection();
        $user_id = (int)($_GET['id'] ?? 0);

        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch() ?: [];

        Renderer::adminRender('users_form', [
            'page_title' => 'Edit User',
            'active'     => 'users',
            'is_new'     => !$user_id,
            'user'       => $user,
            'user_id'    => $user_id,
            'errors'     => [],
        ]);
    }

    public function save() {
        SecurityService::verifyCsrf();
        $db      = Database::getConnection();
        $user_id = (int)($_POST['id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $role    = in_array($_POST['role'] ?? '', ['admin', 'customer']) ? $_POST['role'] : 'customer';
        $pass    = $_POST['password'] ?? '';
        $errors  = [];

        if (!$name)                                     $errors[] = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
        if (!$user_id && strlen($pass) < 6)             $errors[] = 'Password must be at least 6 characters.';
        if ($pass && strlen($pass) < 6)                 $errors[] = 'Password must be at least 6 characters.';

        if (!$errors) {
            $check = $user_id
                ? $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?")
                : $db->prepare("SELECT id FROM users WHERE email = ?");
            $user_id ? $check->execute([$email, $user_id]) : $check->execute([$email]);

            if ($check->fetch()) {
                $errors[] = 'Email already in use.';
            }
        }

        if (!$errors) {
            if ($user_id) {
                if ($pass) {
                    $db->prepare("UPDATE users SET name=?, email=?, role=?, password_hash=? WHERE id=?")
                       ->execute([$name, $email, $role, password_hash($pass, PASSWORD_DEFAULT), $user_id]);
                } else {
                    $db->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?")
                       ->execute([$name, $email, $role, $user_id]);
                }
                flash('msg', 'User updated.');
            } else {
                $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)")
                   ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
                flash('msg', 'User created.');
            }
            redirect('/admin/users');
        }

        Renderer::adminRender('users_form', [
            'page_title' => ($user_id ? 'Edit' : 'Add') . ' User',
            'active'     => 'users',
            'is_new'     => !$user_id,
            'user'       => compact('name', 'email', 'role'),
            'user_id'    => $user_id,
            'errors'     => $errors,
        ]);
    }

    public function delete() {
        $user_id = (int)($_GET['id'] ?? 0);
        if ($user_id === (int)current_user()['id']) {
            flash('err', 'You cannot delete your own account.');
        } else if ($user_id) {
            Database::getConnection()->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
            flash('msg', 'User deleted.');
        }
        redirect('/admin/users');
    }
}
