<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\CartItem;
use App\Services\PricingService;

class TieredPricingTest extends TestCase {
    private $pricingService;
    private $vatService;
    private $promotionEvaluator;
    private $settings;
    private $logger;

    public function setUp(): void {
        $db = \App\Core\Database::getConnection();
        $this->logger = new \Tests\NullLogger();
        $cache = new \Tests\NullCache();
        
        $categoryRepo = new \App\Repositories\CategoryRepository($db, $this->logger);
        $categoryService = new \App\Services\CategoryService($categoryRepo, $this->logger, $cache);
        
        $this->vatService = new \App\Services\VatService();
        $this->promotionEvaluator = new \App\Services\PromotionEvaluator($categoryService);
        
        $settingsRepo = new \App\Repositories\SettingsRepository($db, $this->logger);
        $this->settings = new \App\Services\SettingsService($settingsRepo, $this->logger, $cache, new \Tests\NullEventDispatcher());
        
        $this->pricingService = new PricingService(
            $this->vatService,
            $this->promotionEvaluator,
            $this->settings,
            new \Tests\NullCurrencyService()
        );
    }

    public function testCalculateItemSubtotalWithTiers(): void {
        $product = new Product($this->logger);
        $product->id = 1;
        $product->price = 10.00;
        $product->tiers = [
            ['min_qty' => 5, 'discount' => 1.00],
            ['min_qty' => 10, 'discount' => 2.00]
        ];

        $item = new CartItem($this->logger);
        $item->product = $product;
        
        // No tier (qty 1)
        $item->qty = 1;
        $this->assertEquals(10.00, $this->pricingService->calculateItemSubtotal($item));

        // First tier (qty 5)
        $item->qty = 5;
        $this->assertEquals(45.00, $this->pricingService->calculateItemSubtotal($item)); // (10-1) * 5

        // Still first tier (qty 9)
        $item->qty = 9;
        $this->assertEquals(81.00, $this->pricingService->calculateItemSubtotal($item)); // (10-1) * 9

        // Second tier (qty 10)
        $item->qty = 10;
        $this->assertEquals(80.00, $this->pricingService->calculateItemSubtotal($item)); // (10-2) * 10
    }

    public function testCalculateItemSubtotalWithVariantsAndTiers(): void {
        $product = new Product($this->logger);
        $product->id = 1;
        $product->price = 10.00;
        $product->tiers = [
            ['min_qty' => 5, 'discount' => 1.00]
        ];

        $variant = new ProductVariant($this->logger);
        $variant->id = 101;
        $variant->price = 12.00; // More expensive variant

        $item = new CartItem($this->logger);
        $item->product = $product;
        $item->variant = $variant;
        
        // No tier (qty 1)
        $item->qty = 1;
        $this->assertEquals(12.00, $this->pricingService->calculateItemSubtotal($item));

        // Tier applied to variant (qty 5)
        $item->qty = 5;
        $this->assertEquals(55.00, $this->pricingService->calculateItemSubtotal($item)); // (12-1) * 5
    }

    public function testCalculateItemSubtotalWithZeroPriceSafety(): void {
        $product = new Product($this->logger);
        $product->id = 1;
        $product->price = 5.00;
        $product->tiers = [
            ['min_qty' => 2, 'discount' => 10.00] // Discount greater than price
        ];

        $item = new CartItem($this->logger);
        $item->product = $product;
        $item->qty = 2;
        
        $this->assertEquals(0.00, $this->pricingService->calculateItemSubtotal($item));
    }
}
