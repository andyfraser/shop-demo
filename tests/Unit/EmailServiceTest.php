<?php

namespace App\Services;

// Mock mail function in the App\Services namespace
function mail($to, $subject, $message, $additional_headers = null, $additional_params = null) {
    global $mock_emails;
    $mock_emails[] = [
        'to' => $to,
        'subject' => $subject,
        'message' => $message,
        'headers' => $additional_headers
    ];
    return true;
}

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\EmailService;
use App\Services\SettingsServiceInterface;
use Tests\NullLogger;

class MockSettingsService implements SettingsServiceInterface {
    private $settings = [];
    public function get(string $key): mixed { return $this->settings[$key] ?? null; }
    public function set(string $key, mixed $value): void { $this->settings[$key] = $value; }
    public function all(): array { return $this->settings; }
    public function getSettings(): \App\Models\Settings { return new \App\Models\Settings(); }
}

class EmailServiceTest extends TestCase {
    private $service;
    private $settings;

    public function setUp(): void {
        global $mock_emails;
        $mock_emails = [];
        
        $this->settings = new MockSettingsService();
        $this->settings->set('site_name', 'Test Shop');
        $this->settings->set('email_from', 'shop@test.local');
        
        $this->service = new EmailService($this->settings, new NullLogger());
    }

    public function testSendVerificationEmail() {
        global $mock_emails;
        
        $this->service->sendVerificationEmail('user@example.com', 'User', 'token123');
        
        $this->assertCount(1, $mock_emails);
        $this->assertEquals('user@example.com', $mock_emails[0]['to']);
        $this->assertStringContainsString('Verify your email', $mock_emails[0]['subject']);
        $this->assertStringContainsString('token123', $mock_emails[0]['message']);
        $this->assertStringContainsString('From: Test Shop <shop@test.local>', $mock_emails[0]['headers']);
    }

    public function testSendStatusUpdateEmail() {
        global $mock_emails;
        
        $this->service->sendStatusUpdateEmail('user@example.com', 123, 'Shipped');
        
        $this->assertCount(1, $mock_emails);
        $this->assertStringContainsString('Order Status Updated #123', $mock_emails[0]['subject']);
        $this->assertStringContainsString('Shipped', $mock_emails[0]['message']);
    }

    public function testSendAbandonedCartEmailWithBaseUrl() {
        global $mock_emails;

        if (!defined('BASE_URL')) {
            define('BASE_URL', '/shop-demo');
        }

        $this->service->sendAbandonedCartEmail('user@example.com', 'User');

        $this->assertCount(1, $mock_emails);
        // The URL should contain both the host and the BASE_URL
        $this->assertStringContainsString('http://localhost/shop-demo/cart', $mock_emails[0]['message']);
    }
}
