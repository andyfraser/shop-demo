<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\JsonResponse;
use App\Core\Validator;
use App\Services\AuthServiceInterface;
use App\Services\SecurityServiceInterface;
use App\Services\SettingsServiceInterface;
use App\Services\UserServiceInterface;
use App\Services\CartServiceInterface;
use App\Core\Events\EventDispatcherInterface;
use App\Events\UserLoggedIn;
use App\Events\UserLoginFailed;

class ApiAuthController {
    public function __construct(
        private AuthServiceInterface $authService,
        private SecurityServiceInterface $securityService,
        private SettingsServiceInterface $settingsService,
        private UserServiceInterface $userService,
        private CartServiceInterface $cartService,
        private Validator $validator,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function login(Request $request): Response {
        $ip = $request->getServer('REMOTE_ADDR', '127.0.0.1');
        if ($this->securityService->isRateLimited('api_login', $ip,
            (int)$this->settingsService->get('login_max_attempts'),
            (int)$this->settingsService->get('login_window_minutes') * 60
        )) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMITED',
                    'message' => 'Too many login attempts. Please try again later.'
                ]
            ], 429);
        }

        $email = trim($request->getPost('email', ''));
        $pass  = $request->getPost('password', '');

        $user = $this->userService->findByEmail($email);

        if (!$user || !password_verify($pass, $user->password_hash)) {
            $this->eventDispatcher->dispatch(new UserLoginFailed($email, $ip));
            $this->securityService->recordRateLimit('api_login', $ip);
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'Invalid email or password.'
                ]
            ], 400);
        }

        $this->eventDispatcher->dispatch(new UserLoggedIn($user));
        $this->securityService->clearRateLimit('api_login', $ip);

        // Generate stateless API Bearer Token
        $token = $this->authService->generateApiTokenForUser($user);
        
        // Sync cart
        $this->cartService->syncOnLogin($user->id);

        return new JsonResponse([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_verified' => (bool)$user->is_verified,
                    'role' => $user->role
                ]
            ]
        ]);
    }

    public function register(Request $request): Response {
        $ip = $request->getServer('REMOTE_ADDR', '127.0.0.1');
        if ($this->securityService->isRateLimited('api_register', $ip,
            (int)$this->settingsService->get('register_max_attempts'),
            (int)$this->settingsService->get('register_window_minutes') * 60
        )) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMITED',
                    'message' => 'Too many registration attempts. Please try again later.'
                ]
            ], 429);
        }

        $post = $request->getPost();
        $name  = trim($post['name'] ?? '');
        $email = trim($post['email'] ?? '');
        $pass  = $post['password'] ?? '';
        $pass2 = $post['password_confirmation'] ?? $post['password2'] ?? '';

        $errors = $this->validator->check($post, [
            'name'     => 'required',
            'email'    => 'required|email',
            'password' => 'required|min_length:' . $this->settingsService->get('password_min_length'),
        ]);

        if (!$errors && $pass !== $pass2) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$errors && $this->userService->findByEmail($email)) {
            $errors[] = 'This email is already registered.';
        }

        if ($errors) {
            $this->securityService->recordRateLimit('api_register', $ip);
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed.',
                    'details' => $errors
                ]
            ], 400);
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $verificationToken = bin2hex(random_bytes(32));
        
        $userData = [
            'name'               => $name,
            'email'              => $email,
            'password_hash'      => $hash,
            'role'               => 'customer',
            'verification_token' => $verificationToken,
            'is_verified'        => 0,
            'address'            => null
        ];

        $this->userService->save($userData);

        $user = $this->userService->findByEmail($email);
        $this->eventDispatcher->dispatch(new \App\Events\UserRegistered($user));

        // Generate stateless API Token
        $token = $this->authService->generateApiTokenForUser($user);
        $this->cartService->syncOnLogin($user->id);

        return new JsonResponse([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_verified' => (bool)$user->is_verified,
                    'role' => $user->role
                ]
            ]
        ], 201);
    }

    public function me(Request $request): Response {
        $user = $this->authService->currentUser();
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Unauthorized'
                ]
            ], 401);
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_verified' => (bool)$user->is_verified,
                    'role' => $user->role
                ]
            ]
        ]);
    }

    public function logout(Request $request): Response {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $this->authService->revokeApiToken($matches[1]);
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'message' => 'Logged out successfully.'
            ]
        ]);
    }
}
