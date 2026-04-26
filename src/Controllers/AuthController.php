<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Core\Validator;
use App\Services\AuthServiceInterface;
use App\Services\SecurityServiceInterface;
use App\Services\SettingsServiceInterface;
use App\Services\EmailServiceInterface;
use App\Services\UserServiceInterface;

class AuthController {
    public function __construct(
        private \PDO $db,
        private Renderer $renderer,
        private AuthServiceInterface $authService,
        private SecurityServiceInterface $securityService,
        private SettingsServiceInterface $settingsService,
        private EmailServiceInterface $emailService,
        private UserServiceInterface $userService,
        private Validator $validator,
        private \Psr\Log\LoggerInterface $logger
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

        $user = $this->userService->findByEmail($email);

        if (!$user || !password_verify($pass, $user->password_hash)) {
            $this->logger->warning("Failed login attempt for {email}", ['email' => $email]);
            $this->securityService->recordRateLimit('login', $_SERVER['REMOTE_ADDR']);
            $errors[] = 'Invalid email or password.';
            
            $this->renderer->render('login', [
                'page_title' => 'Sign In',
                'errors'     => $errors,
                'email'      => $email,
            ]);
        } else {
            $this->logger->info("User logged in: {email}", ['email' => $email]);
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
            if ($this->userService->findByEmail($email)) {
                $errors[] = 'This email is already registered.';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $token = bin2hex(random_bytes(32));
                
                $userData = [
                    'name'               => $name,
                    'email'              => $email,
                    'password_hash'      => $hash,
                    'role'               => 'customer',
                    'verification_token' => $token,
                    'is_verified'        => 0,
                    'address'            => null
                ];

                $this->userService->save($userData);

                $this->logger->info("New user registered: {email}", ['email' => $email]);
                $this->emailService->sendVerificationEmail($email, $name, $token);

                $user = $this->userService->findByEmail($email);
                $this->authService->login($user);
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

        $user = $this->userService->findByVerificationToken($token);

        if ($user) {
            $user->is_verified = 1;
            $user->verification_token = null;
            $this->userService->save($user, $user->id);
            
            $this->logger->info("Email verified for user: {email}", ['email' => $user->email]);
            
            // If logged in as this user, update session
            $current = $this->authService->currentUser();
            if ($current && $current->id === $user->id) {
                $this->authService->login($user);
            }
            
            redirect('/?msg=verified');
        } else {
            redirect('/?msg=verify_invalid');
        }
    }

    public function resendVerification() {
        $user = $this->authService->currentUser();
        if (!$user || $user->isVerified()) {
            redirect('/');
        }

        $user->verification_token = bin2hex(random_bytes(32));
        $this->userService->save($user, $user->id);

        $this->emailService->sendVerificationEmail($user->email, $user->name, $user->verification_token);

        redirect('/?msg=verify_sent');
    }

    public function logout() {
        $user = $this->authService->currentUser();
        if ($user) {
            $this->logger->notice("User logged out: {email}", ['email' => $user->email]);
        }
        $this->authService->logout();
        redirect('/');
    }
}
