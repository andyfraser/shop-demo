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
        $this->service = new CategoryService($this->db);
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
}
