<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WishlistService;
use App\Services\WishlistServiceInterface;
use App\Services\ProductService;
use App\Services\AttributeService;
use App\Core\Database;

class WishlistServiceTest extends TestCase {
    private WishlistServiceInterface $service;
    private \PDO $db;

    public function setUp() {
        $this->db = Database::getConnection();
        $logger = new \Tests\NullLogger();
        $cache = new \Tests\NullCache();
        $eventDispatcher = new \Tests\NullEventDispatcher();
        $attrRepository = new \App\Repositories\AttributeRepository($this->db, $logger);
        $attrService = new AttributeService($attrRepository, $logger, new \Tests\NullCache());
        $categoryRepo = new \App\Repositories\CategoryRepository($this->db, $logger);
        $categoryService = new \App\Services\CategoryService($categoryRepo, $logger, $cache);
        $evaluator = new \App\Services\PromotionEvaluator($categoryService);
        $promotionRepository = new \App\Repositories\PromotionRepository($this->db, $logger);
        $promoService = new \App\Services\PromotionService($promotionRepository, $evaluator, $logger, new \Tests\NullCache());
        $repository = new \App\Repositories\ProductRepository($this->db, $logger);
        $variantService = new \App\Services\ProductVariantService($repository, $attrService, $eventDispatcher);
        $productService = new ProductService($repository, $attrService, $promoService, $variantService, $logger, new \Tests\NullCache(), new \Tests\NullEventDispatcher());
        $wishlistRepo = new \App\Repositories\WishlistRepository($this->db, $logger);
        $userRepo = new \App\Repositories\UserRepository($this->db, $logger);
        $this->service = new WishlistService($wishlistRepo, $productService, $userRepo, $logger);

        // Clean up wishlist for test
        $this->db->exec("DELETE FROM wishlists");
        $this->db->exec("DELETE FROM wishlist_settings");
    }

    public function testAddToWishlist() {
        // User 1, Product 1 (from seed data)
        $result = $this->service->addToWishlist(1, 1);
        $this->assertTrue($result);

        $this->assertTrue($this->service->isInWishlist(1, 1));
        
        // Try adding again (should return false due to unique constraint)
        $result = $this->service->addToWishlist(1, 1);
        $this->assertFalse($result);
    }

    public function testGetUserWishlist() {
        $this->service->addToWishlist(1, 1);
        $this->service->addToWishlist(1, 2);

        $wishlist = $this->service->getUserWishlist(1);
        $this->assertCount(2, $wishlist);
        
        $ids = array_map(fn($p) => $p->id, $wishlist);
        $this->assertTrue(in_array(1, $ids));
        $this->assertTrue(in_array(2, $ids));
    }

    public function testRemoveFromWishlist() {
        $this->service->addToWishlist(1, 1);
        $this->assertTrue($this->service->isInWishlist(1, 1));

        $result = $this->service->removeFromWishlist(1, 1);
        $this->assertTrue($result);
        $this->assertFalse($this->service->isInWishlist(1, 1));
    }

    public function testGetSettings() {
        $settings = $this->service->getSettings(1);
        $this->assertEquals(0, $settings['is_public']);
        $this->assertNull($settings['share_hash']);
    }

    public function testTogglePrivacy() {
        // Toggle to public
        $settings = $this->service->togglePrivacy(1, true);
        $this->assertEquals(1, $settings['is_public']);
        $this->assertNotNull($settings['share_hash']);
        $hash = $settings['share_hash'];

        // Check if settings persisted
        $settings = $this->service->getSettings(1);
        $this->assertEquals(1, $settings['is_public']);
        $this->assertEquals($hash, $settings['share_hash']);

        // Toggle back to private
        $settings = $this->service->togglePrivacy(1, false);
        $this->assertEquals(0, $settings['is_public']);
        $this->assertEquals($hash, $settings['share_hash']); // Hash remains but is_public is 0
    }

    public function testGetSharedWishlist() {
        // Make public
        $settings = $this->service->togglePrivacy(1, true);
        $hash = $settings['share_hash'];

        // Add some items
        $this->service->addToWishlist(1, 1);

        $shared = $this->service->getSharedWishlist($hash);
        $this->assertNotNull($shared);
        $this->assertEquals(1, $shared['user']->id);
        $this->assertCount(1, $shared['products']);

        // Test with invalid hash
        $this->assertNull($this->service->getSharedWishlist('invalid_hash'));

        // Test when private
        $this->service->togglePrivacy(1, false);
        $this->assertNull($this->service->getSharedWishlist($hash));
    }
}
