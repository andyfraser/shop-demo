<?php

namespace Tests\Integration;

use Tests\TestCase;
use Tests\RequestSimulation;
use App\Core\Responses\RedirectResponse;
use App\Core\Responses\HtmlResponse;

class MiddlewareTest extends TestCase {
    use RequestSimulation;

    public function testAuthMiddlewareRedirectsGuest() {
        $response = $this->get('/account');
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeaders()['Location']);
    }

    public function testAdminMiddlewareRedirectsGuest() {
        $response = $this->get('/admin');
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeaders()['Location']);
    }

    public function testAdminMiddlewareRedirectsNonAdmin() {
        $session = [
            'user' => ['id' => 2] // Regular user from seed (Jane Smith)
        ];
        
        $response = $this->get('/admin', $session);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/', $response->getHeaders()['Location']);
    }

    public function testAdminMiddlewareAllowsAdmin() {
        $session = [
            'user' => ['id' => 1] // Admin from seed
        ];
        
        $response = $this->get('/admin', $session);
        // Should NOT be a RedirectResponse to /login or /
        $isRedirectToHomeOrLogin = $response instanceof RedirectResponse && in_array($response->getHeaders()['Location'], ['/login', '/']);
        $this->assertFalse($isRedirectToHomeOrLogin);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGuestMiddlewareRedirectsLoggedInUser() {
        $session = [
            'user' => ['id' => 2]
        ];
        
        $response = $this->get('/login', $session);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/', $response->getHeaders()['Location']);
    }

    public function testVerifiedMiddlewareRedirectsUnverifiedUser() {
        // Create an unverified user
        $db = $this->container->get(\PDO::class);
        $db->prepare("INSERT INTO users (name, email, password_hash, role, is_verified) VALUES (?, ?, ?, ?, ?)")
           ->execute(['Unverified', 'unverified@example.com', 'hash', 'customer', 0]);
        $userId = $db->lastInsertId();

        $session = [
            'user' => ['id' => $userId]
        ];
        
        $response = $this->get('/checkout', $session);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/cart?msg=verify_required', $response->getHeaders()['Location']);
    }
}
