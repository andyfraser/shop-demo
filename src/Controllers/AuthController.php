<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Renderer;
use App\Core\Validator;
use App\Services\AuthServiceInterface;
use App\Services\SecurityServiceInterface;
use App\Services\SettingsServiceInterface;
use App\Services\EmailServiceInterface;
use App\Services\UserServiceInterface;
use App\Services\CartServiceInterface;
use App\Core\Events\EventDispatcherInterface;
use App\Events\UserLoggedIn;
use App\Events\UserLoginFailed;

class AuthController {
    public function __construct(
        private \PDO $db,
        private Renderer $renderer,
        private AuthServiceInterface $authService,
        private SecurityServiceInterface $securityService,
        private SettingsServiceInterface $settingsService,
        private EmailServiceInterface $emailService,
        private UserServiceInterface $userService,
        private CartServiceInterface $cartService,
        private Validator $validator,
        private \Psr\Log\LoggerInterface $logger,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function showLogin(Request $request): Response {
        return new HtmlResponse($this->renderer->render('login', [
            'page_title' => 'Sign In',
            'errors'     => [],
            'email'      => '',
        ]));
    }

    public function login(Request $request): Response {
        $ip = $request->getServer('REMOTE_ADDR');
        if ($this->securityService->isRateLimited('login', $ip,
            (int)$this->settingsService->get('login_max_attempts'),
            (int)$this->settingsService->get('login_window_minutes') * 60
        )) {
            return new HtmlResponse('Too many attempts. Please try again later.', 429);
        }

        $email = trim($request->getPost('email', ''));
        $pass  = $request->getPost('password', '');
        $errors = [];

        $user = $this->userService->findByEmail($email);

        if (!$user || !password_verify($pass, $user->password_hash)) {
            $this->eventDispatcher->dispatch(new UserLoginFailed($email, $ip));
            $this->securityService->recordRateLimit('login', $ip);
            $errors[] = 'Invalid email or password.';
            
            return new HtmlResponse($this->renderer->render('login', [
                'page_title' => 'Sign In',
                'errors'     => $errors,
                'email'      => $email,
            ]));
        } else {
            $this->eventDispatcher->dispatch(new UserLoggedIn($user));
            $this->securityService->clearRateLimit('login', $ip);
            $remember = !empty($request->getPost('remember_me'));
            $this->authService->login($user, $remember);
            $this->cartService->syncOnLogin($user->id);
            $redirect = $_SESSION['redirect_after_login'] ?? '/';
            unset($_SESSION['redirect_after_login']);
            return new RedirectResponse($redirect);
        }
    }

    public function showRegister(Request $request): Response {
        return new HtmlResponse($this->renderer->render('register', [
            'page_title' => 'Create Account',
            'errors'     => [],
            'name'       => '',
            'email'      => '',
        ]));
    }

    public function register(Request $request): Response {
        $ip = $request->getServer('REMOTE_ADDR');
        if ($this->securityService->isRateLimited('register', $ip,
            (int)$this->settingsService->get('register_max_attempts'),
            (int)$this->settingsService->get('register_window_minutes') * 60
        )) {
            return new HtmlResponse('Too many attempts. Please try again later.', 429);
        }

        $post = $request->getPost();
        $name  = trim($post['name'] ?? '');
        $email = trim($post['email'] ?? '');
        $pass  = $post['password'] ?? '';
        $pass2 = $post['password2'] ?? '';

        $errors = $this->validator->check($post, [
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

                $user = $this->userService->findByEmail($email);
                $this->eventDispatcher->dispatch(new \App\Events\UserRegistered($user));

                $this->authService->login($user);
                $this->cartService->syncOnLogin($user->id);
                return new RedirectResponse('/?msg=verify_sent');
            }
        }
        
        if ($errors) {
            $this->securityService->recordRateLimit('register', $ip);
            return new HtmlResponse($this->renderer->render('register', [
                'page_title' => 'Create Account',
                'errors'     => $errors,
                'name'       => $name,
                'email'      => $email,
            ]));
        }
        
        return new RedirectResponse('/');
    }

    public function verifyEmail(Request $request): Response {
        $token = $request->getQuery('token', '');
        if (!$token) {
            return new RedirectResponse('/');
        }

        $user = $this->userService->findByVerificationToken($token);

        if ($user) {
            $user->is_verified = 1;
            $user->verification_token = null;
            $this->userService->save($user, $user->id);
            
            $this->eventDispatcher->dispatch(new \App\Events\EmailVerified($user));
            
            // If logged in as this user, update session
            $current = $this->authService->currentUser();
            if ($current && $current->id === $user->id) {
                $this->authService->login($user);
            }
            
            return new RedirectResponse('/?msg=verified');
        } else {
            return new RedirectResponse('/?msg=verify_invalid');
        }
    }

    public function resendVerification(Request $request): Response {
        $user = $this->authService->currentUser();
        if (!$user || $user->isVerified()) {
            return new RedirectResponse('/');
        }

        $user->verification_token = bin2hex(random_bytes(32));
        $this->userService->save($user, $user->id);

        $this->emailService->sendVerificationEmail($user->email, $user->name, $user->verification_token);

        return new RedirectResponse('/?msg=verify_sent');
    }

    public function logout(Request $request): Response {
        $user = $this->authService->currentUser();
        if ($user) {
            $this->logger->notice("User logged out: {email}", ['email' => $user->email]);
        }
        $this->authService->logout();
        return new RedirectResponse('/');
    }
}
