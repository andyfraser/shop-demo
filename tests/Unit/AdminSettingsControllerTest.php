<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Controllers\AdminSettingsController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\RedirectResponse;
use App\Core\Responses\HtmlResponse;
use App\Core\Cache\CacheInterface;
use App\Services\SettingsServiceInterface;
use Tests\NullLogger;
use Tests\NullCache;

class MockCache implements CacheInterface {
    public bool $shouldSucceed = true;
    public bool $cleared = false;

    public function get(string $key, mixed $default = null): mixed { return $default; }
    public function set(string $key, mixed $value, int $ttl = 3600): bool { return true; }
    public function delete(string $key): bool { return true; }
    public function clear(): bool {
        $this->cleared = true;
        return $this->shouldSucceed;
    }
    public function has(string $key): bool { return false; }
}

class SettingsControllerMockSettingsService implements SettingsServiceInterface {
    public array $settings = [];
    public function getSettings(): \App\Models\Settings {
        $s = new \App\Models\Settings(new NullLogger());
        $s->fill($this->settings);
        return $s;
    }
    public function get(string $key): mixed { return $this->settings[$key] ?? null; }
    public function all(): array { return $this->settings; }
    public function set(string $key, mixed $value): void { $this->settings[$key] = $value; }
}

class SettingsControllerMockSecurityService implements \App\Services\SecurityServiceInterface {
    public function csrfToken(): string { return 'token'; }
    public function csrfField(): string { return 'field'; }
    public function validateCsrf(?string $token): bool { return true; }
    public function isRateLimited(string $action, string $ip, int $limit, int $windowSeconds): bool { return false; }
    public function recordRateLimit(string $action, string $ip): void {}
    public function clearRateLimit(string $action, string $ip): void {}
}

class SettingsControllerStubRenderer extends \App\Core\Renderer {
    public function __construct() {}
    public function adminRender(string $template, array $vars = []): string {
        return "rendered:" . $template;
    }
}

class AdminSettingsControllerTest extends TestCase {
    public function testClearCacheSuccess() {
        $renderer = new SettingsControllerStubRenderer();
        $security = new SettingsControllerMockSecurityService();
        $settingsService = new SettingsControllerMockSettingsService();
        $logger = new NullLogger();
        $cache = new MockCache();
        $cache->shouldSucceed = true;

        $controller = new AdminSettingsController(
            $renderer,
            $security,
            $settingsService,
            $logger,
            $cache
        );

        $request = new Request([], [], [], [], [], []);
        $response = $controller->clearCache($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue($cache->cleared);
        $this->assertEquals('/admin/settings', $response->getHeaders()['Location']);
    }

    public function testClearCacheFailure() {
        $renderer = new SettingsControllerStubRenderer();
        $security = new SettingsControllerMockSecurityService();
        $settingsService = new SettingsControllerMockSettingsService();
        $logger = new NullLogger();
        $cache = new MockCache();
        $cache->shouldSucceed = false;

        $controller = new AdminSettingsController(
            $renderer,
            $security,
            $settingsService,
            $logger,
            $cache
        );

        $request = new Request([], [], [], [], [], []);
        $response = $controller->clearCache($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue($cache->cleared);
        $this->assertEquals('/admin/settings', $response->getHeaders()['Location']);
    }
}
