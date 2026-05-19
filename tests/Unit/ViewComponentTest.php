<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\View\Components\StatusBadge;
use App\View\Components\Alert;
use App\View\Components\ProductCard;
use App\Models\Product;

use Tests\NullLogger;
use App\Core\Container;
use App\Services\ImageServiceInterface;
use App\Services\SettingsService;
use App\Repositories\SettingsRepository;
use App\Core\Database;

class ViewComponentTest extends TestCase {
    public function setUp() {
        $container = Container::getInstance();
        $db = Database::getConnection();
        $logger = new NullLogger();

        $container->set(SettingsService::class, function() use ($db, $logger) {
            $repo = new SettingsRepository($db, $logger);
            return new SettingsService($repo, $logger, new \Tests\NullCache());
        });

        $container->set(ImageServiceInterface::class, function() {
            return new class implements ImageServiceInterface {
                public function getUrl($filename, $size = 'original'): string { return "/uploads/$filename"; }
                public function processUpload(array $file): ?string { return ""; }
                public function delete($filename): void {}
            };
        });
    }

    public function testStatusBadgeRendersCorrectly() {
        $badge = new StatusBadge('Pending', 'badge-warning');
        $html = $badge->render();
        
        $this->assertStringContainsString('Pending', $html);
        $this->assertStringContainsString('badge-warning', $html);
        $this->assertStringContainsString('<span class="badge', $html);
    }

    public function testAlertRendersCorrectly() {
        $alert = new Alert('Success message', 'success');
        $html = $alert->render();
        
        $this->assertStringContainsString('Success message', $html);
        $this->assertStringContainsString('alert-success', $html);
        $this->assertStringContainsString('onclick="this.remove()"', $html);
    }

    public function testProductCardRendersCorrectly() {
        $product = new Product(new NullLogger());
        $product->name = 'Test Product';
        $product->slug = 'test-product';
        $product->price = 19.99;
        $product->featured = false;
        $product->created_at = date('Y-m-d H:i:s');
        
        $card = new ProductCard($product);
        $html = $card->render();
        
        $this->assertStringContainsString('Test Product', $html);
        $this->assertStringContainsString('/product/test-product', $html);
    }
}
