<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CategoryServiceInterface;
use App\Services\CategoryService;
use App\Models\Category;
use App\Core\Database;

class CategoryServiceTest extends TestCase {
    private CategoryServiceInterface $service;
    private \PDO $db;

    public function setUp() {
        $this->db = Database::getConnection();
        $logger = new \Tests\NullLogger();
        $cache = new \Tests\NullCache();
        $repository = new \App\Repositories\CategoryRepository($this->db, $logger);
        $this->service = new CategoryService($repository, $logger, $cache);
    }

    public function testGetTree() {
        $tree = $this->service->getTree();
        $this->assertIsArray($tree);
        if (!empty($tree)) {
            $this->assertInstanceOf(Category::class, $tree[0]);
            $this->assertIsArray($tree[0]->children);
        }
    }

    public function testFindById() {
        $category = $this->service->findById(1);
        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals(1, $category->id);
    }

    public function testSave() {
        $name = 'Test Category ' . time();
        $data = [
            'name'        => $name,
            'parent_id'   => null,
            'description' => 'Test Desc',
            'icon'        => '📦',
        ];

        $id = $this->service->save($data);
        $this->assertGreaterThan(0, $id);

        $category = $this->service->findById($id);
        $this->assertEquals($name, $category->name);
        $this->assertStringContainsString('test-category', $category->slug);
    }

    public function testGetBreadcrumb() {
        // In seed data, Laptops (ID 1) should be at root. 
        // We might need a deeper one for better testing if available.
        // Let's find a category with a parent if it exists.
        $stmt = $this->db->query("SELECT id FROM categories WHERE parent_id IS NOT NULL LIMIT 1");
        $childId = $stmt->fetchColumn();

        if ($childId) {
            $crumbs = $this->service->getBreadcrumb($childId);
            $this->assertTrue(count($crumbs) >= 2);
            $lastCrumb = end($crumbs);
            $this->assertEquals($childId, $lastCrumb->id);
        } else {
            // Fallback to ID 1 if no hierarchy exists in current DB
            $crumbs = $this->service->getBreadcrumb(1);
            $this->assertCount(1, $crumbs);
            $this->assertEquals(1, $crumbs[0]->id);
        }
    }
}
