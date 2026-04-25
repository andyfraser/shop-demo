<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Core\Validator;
use App\Services\AuthService;
use App\Services\SecurityService;
use App\Services\SettingsService;
use App\Services\EmailService;

class AuthController {
    public function __construct(
        private \PDO $db,
        private Renderer $renderer,
        private AuthService $authService,
        private SecurityService $securityService,
        private SettingsService $settingsService,
        private EmailService $emailService,
        private Validator $validator
    ) {}

    public function showLogin() {
        if ($this->authService->currentUser()) {
            redirect('/');
        }
        $this->renderer->render('login', [
            'page_title' => 'Sign In',
            'errors'     => [],
            'email'      => '',
        ]);
    }

    public function login() {
        if ($this->authService->currentUser()) {
            redirect('/');
        }
        
        $this->securityService->verifyCsrf();
        $this->securityService->checkRateLimit('login', $_SERVER['REMOTE_ADDR'],
            (int)$this->settingsService->get('login_max_attempts'),
            (int)$this->settingsService->get('login_window_minutes') * 60
        );

        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $errors = [];

        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($pass, $user['password_hash'])) {
            $this->securityService->recordRateLimit('login', $_SERVER['REMOTE_ADDR']);
            $errors[] = 'Invalid email or password.';
            
            $this->renderer->render('login', [
                'page_title' => 'Sign In',
                'errors'     => $errors,
                'email'      => $email,
            ]);
        } else {
            $this->securityService->clearRateLimit('login', $_SERVER['REMOTE_ADDR']);
            $remember = !empty($_POST['remember_me']);
            $this->authService->login($user, $remember);
            redirect($_SESSION['redirect_after_login'] ?? '/');
        }
    }

    public function showRegister() {
        if ($this->authService->currentUser()) {
            redirect('/');
        }
        $this->renderer->render('register', [
            'page_title' => 'Create Account',
            'errors'     => [],
            'name'       => '',
            'email'      => '',
        ]);
    }

    public function register() {
        if ($this->authService->currentUser()) {
            redirect('/');
        }

        $this->securityService->verifyCsrf();
        $this->securityService->checkRateLimit('register', $_SERVER['REMOTE_ADDR'],
            (int)$this->settingsService->get('register_max_attempts'),
            (int)$this->settingsService->get('register_window_minutes') * 60
        );

        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $pass2 = $_POST['password2'] ?? '';

        $errors = $this->validator->check($_POST, [
            'name'     => 'required',
            'email'    => 'required|email',
            'password' => 'required|min_length:' . $this->settingsService->get('password_min_length'),
        ]);
        if (!$errors && $pass !== $pass2) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$errors) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'This email is already registered.';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $token = bin2hex(random_bytes(32));
                
                $this->db->prepare("INSERT INTO users (name, email, password_hash, role, verification_token, is_verified) VALUES (?, ?, ?, 'customer', ?, 0)")
                   ->execute([$name, $email, $hash, $token]);

                $this->emailService->sendVerificationEmail($email, $name, $token);

                $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $this->authService->login($stmt->fetch());
                redirect('/?msg=verify_sent');
            }
        }
        
        if ($errors) {
            $this->securityService->recordRateLimit('register', $_SERVER['REMOTE_ADDR']);
            $this->renderer->render('register', [
                'page_title' => 'Create Account',
                'errors'     => $errors,
                'name'       => $name,
                'email'      => $email,
            ]);
        }
    }

    public function verifyEmail() {
        $token = $_GET['token'] ?? '';
        if (!$token) {
            redirect('/');
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE verification_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            $this->db->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?")
               ->execute([$user['id']]);
            
            // If logged in as this user, update session
            $current = $this->authService->currentUser();
            if ($current && $current['id'] === $user['id']) {
                $user['is_verified'] = 1;
                $user['verification_token'] = null;
                $this->authService->login($user);
            }
            
            redirect('/?msg=verified');
        } else {
            redirect('/?msg=verify_invalid');
        }
    }

    public function resendVerification() {
        $user = $this->authService->currentUser();
        if (!$user || !empty($user['is_verified'])) {
            redirect('/');
        }

        $token = bin2hex(random_bytes(32));
        $this->db->prepare("UPDATE users SET verification_token = ? WHERE id = ?")
           ->execute([$token, $user['id']]);

        $this->emailService->sendVerificationEmail($user['email'], $user['name'], $token);

        redirect('/?msg=verify_sent');
    }

    public function logout() {
        $this->authService->logout();
        redirect('/');
    }
}
