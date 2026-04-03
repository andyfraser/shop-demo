<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Renderer;
use App\Core\Validator;
use App\Services\AuthService;
use App\Services\SecurityService;
use App\Services\SettingsService;

class AuthController {
    public function showLogin() {
        if (AuthService::currentUser()) {
            redirect('/');
        }
        Renderer::render('login', [
            'page_title' => 'Sign In',
            'errors'     => [],
            'email'      => '',
        ]);
    }

    public function login() {
        if (AuthService::currentUser()) {
            redirect('/');
        }
        
        SecurityService::verifyCsrf();
        SecurityService::checkRateLimit('login', $_SERVER['REMOTE_ADDR'],
            (int)SettingsService::get('login_max_attempts'),
            (int)SettingsService::get('login_window_minutes') * 60
        );

        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $errors = [];

        $stmt = Database::getConnection()->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($pass, $user['password_hash'])) {
            SecurityService::recordRateLimit('login', $_SERVER['REMOTE_ADDR']);
            $errors[] = 'Invalid email or password.';
            
            Renderer::render('login', [
                'page_title' => 'Sign In',
                'errors'     => $errors,
                'email'      => $email,
            ]);
        } else {
            SecurityService::clearRateLimit('login', $_SERVER['REMOTE_ADDR']);
            AuthService::login($user);
            redirect($_SESSION['redirect_after_login'] ?? '/');
        }
    }

    public function showRegister() {
        if (AuthService::currentUser()) {
            redirect('/');
        }
        Renderer::render('register', [
            'page_title' => 'Create Account',
            'errors'     => [],
            'name'       => '',
            'email'      => '',
        ]);
    }

    public function register() {
        if (AuthService::currentUser()) {
            redirect('/');
        }

        SecurityService::verifyCsrf();
        SecurityService::checkRateLimit('register', $_SERVER['REMOTE_ADDR'],
            (int)SettingsService::get('register_max_attempts'),
            (int)SettingsService::get('register_window_minutes') * 60
        );

        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $pass2 = $_POST['password2'] ?? '';

        $errors = Validator::check($_POST, [
            'name'     => 'required',
            'email'    => 'required|email',
            'password' => 'required|min_length:' . SettingsService::get('password_min_length'),
        ]);
        if (!$errors && $pass !== $pass2) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$errors) {
            $stmt = Database::getConnection()->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'This email is already registered.';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                Database::getConnection()->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'customer')")
                   ->execute([$name, $email, $hash]);

                $stmt = Database::getConnection()->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                AuthService::login($stmt->fetch());
                redirect('/');
            }
        }
        
        if ($errors) {
            SecurityService::recordRateLimit('register', $_SERVER['REMOTE_ADDR']);
            Renderer::render('register', [
                'page_title' => 'Create Account',
                'errors'     => $errors,
                'name'       => $name,
                'email'      => $email,
            ]);
        }
    }

    public function logout() {
        AuthService::logout();
        redirect('/');
    }
}
