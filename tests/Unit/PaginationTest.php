<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\QueryCriteria;

class PaginationTest extends TestCase {
    public function testQueryCriteriaFromRequestDefaultLimit() {
        // Current behavior: limit is null if not provided
        $get = [];
        $criteria = QueryCriteria::fromRequest($get);
        $this->assertNull($criteria->getLimit());

        // Test with explicit per_page
        $get = ['per_page' => '24'];
        $criteria = QueryCriteria::fromRequest($get);
        $this->assertEquals(24, $criteria->getLimit());

        // Test with 'all'
        $get = ['per_page' => 'all'];
        $criteria = QueryCriteria::fromRequest($get);
        $this->assertNull($criteria->getLimit());
    }

    public function testProposedDefaultLimit() {
        // We want to be able to specify a default limit
        $get = [];
        // If we modify fromRequest to accept a default:
        $criteria = QueryCriteria::fromRequest($get, 12);
        $this->assertEquals(12, $criteria->getLimit());

        // Explicit per_page should still override default
        $get = ['per_page' => '48'];
        $criteria = QueryCriteria::fromRequest($get, 12);
        $this->assertEquals(48, $criteria->getLimit());

        // Explicit 'all' should still result in null limit
        $get = ['per_page' => 'all'];
        $criteria = QueryCriteria::fromRequest($get, 12);
        $this->assertNull($criteria->getLimit());
    }

    public function testTotalPagesCalculationInController() {
        // Simulate StorefrontController::products behavior
        $total_products = 25;
        
        // No per_page in request
        $get = [];
        $criteria = QueryCriteria::fromRequest($get, 12);
        $limit = $criteria->getLimit();
        $total_pages = $limit !== null ? (int)ceil($total_products / $limit) : 1;
        $this->assertEquals(3, $total_pages); // 25 / 12 = 2.08 -> 3 pages

        // per_page=all in request
        $get = ['per_page' => 'all'];
        $criteria = QueryCriteria::fromRequest($get, 12);
        $limit = $criteria->getLimit();
        $total_pages = $limit !== null ? (int)ceil($total_products / $limit) : 1;
        $this->assertEquals(1, $total_pages);
    }
}
