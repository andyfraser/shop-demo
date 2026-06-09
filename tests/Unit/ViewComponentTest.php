<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\View\Components\StatusBadge;
use App\View\Components\Alert;
use App\View\Components\ProductCard;
use App\View\Components\StarRating;
use App\View\Components\Pagination;
use App\View\Components\Breadcrumbs;
use App\View\Components\SearchBar;
use App\View\Components\OrderSummary;
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

        $container->set(\App\Services\CurrencyServiceInterface::class, function() {
            return new \Tests\NullCurrencyService();
        });

        $container->set(\App\Services\PricingServiceInterface::class, function($c) {
            return new \App\Services\PricingService(
                new \App\Services\VatService(),
                new \App\Services\PromotionEvaluator(new \App\Services\CategoryService(
                    new \App\Repositories\CategoryRepository(\App\Core\Database::getConnection(), new \Tests\NullLogger()),
                    new \Tests\NullLogger(),
                    new \Tests\NullCache()
                )),
                $c->get(\App\Services\SettingsServiceInterface::class),
                $c->get(\App\Services\CurrencyServiceInterface::class)
            );
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
        $this->assertStringContainsString('class="quickview-btn"', $html);
        $this->assertStringContainsString('data-slug="test-product"', $html);
    }

    public function testStarRatingRendersCorrectly() {
        $rating = new StarRating(3.5, true);
        $html = $rating->render();

        $this->assertStringContainsString('★', $html);
        $this->assertStringContainsString('½', $html);
        $this->assertStringContainsString('☆', $html);
        $this->assertStringContainsString('(3.5/5)', $html);
    }

    public function testPaginationRendersCorrectly() {
        $pagination = new Pagination(2, 5, '/search', ['q' => 'shoes']);
        $html = $pagination->render();

        $this->assertStringContainsString('btn-primary', $html); // active page 2
        $this->assertStringContainsString('/search?q=shoes&amp;page=1', $html);
        $this->assertStringContainsString('/search?q=shoes&amp;page=2', $html);
        $this->assertStringContainsString('/search?q=shoes&amp;page=5', $html);
    }

    public function testBreadcrumbsRendersCorrectly() {
        $breadcrumbs = new Breadcrumbs([
            'Home' => '/',
            'Categories' => '/categories',
            'Running Shoes'
        ]);
        $html = $breadcrumbs->render();

        $this->assertStringContainsString('<a href="/">Home</a>', $html);
        $this->assertStringContainsString('<a href="/categories">Categories</a>', $html);
        $this->assertStringContainsString('<span>Running Shoes</span>', $html);
        $this->assertStringContainsString('›', $html);
    }

    public function testSearchBarRendersCorrectly() {
        // Desktop
        $searchBar = new SearchBar('sneakers', false);
        $html = $searchBar->render();
        $this->assertStringContainsString('class="header-search"', $html);
        $this->assertStringContainsString('value="sneakers"', $html);

        // Mobile
        $searchBarMobile = new SearchBar('sneakers', true);
        $htmlMobile = $searchBarMobile->render();
        $this->assertStringContainsString('class="mobile-search-bar"', $htmlMobile);
        $this->assertStringContainsString('value="sneakers"', $htmlMobile);
    }

    public function testOrderSummaryRendersCorrectly() {
        $cartMock = new class implements \App\Services\CartServiceInterface {
            public function get(): array { return []; }
            public function add(int $productId, int $qty = 1, ?int $variantId = null, ?array $metadata = null): void {}
            public function isVirtualOnly(): bool { return false; }
            public function remove(string $key): void {}
            public function update(string $key, int $qty): void {}
            public function clear(): void {}
            public function count(): int { return 2; }
            public function items(): array { return []; }
            public function total(): float { return 100.00; }
            public function totalVat(): float { return 20.00; }
            public function applyPromoCode(string $code): bool { return true; }
            public function removePromoCode(?string $code = null): void {}
            public function getAppliedPromotions(): array { return []; }
            public function getPromotionDiscount(\App\Models\Promotion $promo): float { return 10.0; }
            public function discount(): float { return 10.00; }
            public function grandTotal(): float { return 90.00; }
            public function syncOnLogin(int $userId): void {}
        };

        // Cart mode
        $summaryCart = new OrderSummary($cartMock, false);
        $htmlCart = $summaryCart->render();
        $this->assertStringContainsString('id="cart-subtotal"', $htmlCart);
        $this->assertStringContainsString('id="cart-total"', $htmlCart);
        $this->assertStringContainsString('id="cart-vat"', $htmlCart);

        // Checkout mode
        $summaryCheckout = new OrderSummary($cartMock, true);
        $htmlCheckout = $summaryCheckout->render();
        $this->assertStringContainsString('id="checkout-subtotal"', $htmlCheckout);
        $this->assertStringContainsString('id="final-total"', $htmlCheckout);
        $this->assertStringContainsString('id="vat-amount"', $htmlCheckout);
    }
}
