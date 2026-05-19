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
        $this->service = new WishlistService($wishlistRepo, $productService, $logger);

        // Clean up wishlist for test
        $this->db->exec("DELETE FROM wishlists");
    }

    public function testAddToWishlist() {
        // User 1, Product 1 (from seed data)
        $result = $this->service->addToWishlist(1, 1);
        $this->assertTrue($result);

        $this->assertTrue($this->service->isInWishlist(1, 1));
        
        // Try adding again (should return false due to unique constraint, or be handled gracefully)
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
}
