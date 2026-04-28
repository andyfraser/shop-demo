<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\ProductServiceInterface;
use App\Services\CategoryServiceInterface;

class StorefrontController {
    public function __construct(
        private ProductServiceInterface $productService,
        private CategoryServiceInterface $categoryService,
        private Renderer $renderer
    ) {}

    public function index() {
        $featured_products = $this->productService->getFeatured(8);

        $this->renderer->render('home', [
            'page_title'       => 'Welcome',
            'featured_products' => $featured_products,
        ]);
    }

    public function search() {
        $query    = trim($_GET['q'] ?? '');
        $products = [];
        $total_products = 0;
        $total_pages    = 1;
        $current_page   = max(1, (int)($_GET['page'] ?? 1));

        $sort = in_array($_GET['sort'] ?? '', ['name', 'price_asc', 'price_desc', 'featured']) ? $_GET['sort'] : 'name';
        $per_page_raw = $_GET['per_page'] ?? '12';
        $per_page_param = $per_page_raw === 'all' ? 'all' : (in_array((int)$per_page_raw, [12, 24]) ? (string)(int)$per_page_raw : '12');
        $per_page = $per_page_param === 'all' ? null : (int)$per_page_param;

        $filters = $this->getFiltersFromRequest();

        if ($query) {
            $total_products = $this->productService->countSearch($query, $filters);
            $products = $this->productService->search($query, $per_page, $current_page, $sort, $filters);
            
            if ($per_page !== null) {
                $total_pages = (int)ceil($total_products / $per_page);
            }
        }

        $available_filters = $this->productService->getAvailableFilters([], $query);

        $data = [
            'page_title'        => $query ? 'Search: ' . $query : 'Search',
            'search_query'      => $query,
            'query'             => $query,
            'products'          => $products,
            'total_products'    => $total_products,
            'total_pages'       => $total_pages,
            'current_page'      => $current_page,
            'sort'              => $sort,
            'per_page_param'    => $per_page_param,
            'available_filters' => $available_filters,
            'active_filters'    => $filters,
        ];

        if ($this->isAjax()) {
            $this->renderer->renderPartial('partials/product_list', $data);
        } else {
            $this->renderer->render('search', $data);
        }
    }

    public function category($slug = '') {
        $category = $this->categoryService->findBySlug($slug);

        if (!$category) {
            http_response_code(404);
            exit('Category not found.');
        }

        $subcategories = $this->categoryService->getSubcategories($category->id);

        $cat_ids = [$category->id];
        $queue = array_map(fn($c) => $c->id, $subcategories);

        while ($queue) {
            $id = array_shift($queue);
            $cat_ids[] = $id;
            $subs = $this->categoryService->getSubcategories($id);
            foreach ($subs as $row) $queue[] = $row->id;
        }

        $sort = in_array($_GET['sort'] ?? '', ['name', 'price_asc', 'price_desc', 'featured']) ? $_GET['sort'] : 'name';
        $per_page_raw = $_GET['per_page'] ?? '12';
        $per_page_param = $per_page_raw === 'all' ? 'all' : (in_array((int)$per_page_raw, [12, 24]) ? (string)(int)$per_page_raw : '12');
        $per_page = $per_page_param === 'all' ? null : (int)$per_page_param;
        $current_page = max(1, (int)($_GET['page'] ?? 1));

        $filters = $this->getFiltersFromRequest();

        $total_products = $this->productService->countByCategory($cat_ids, $filters);
        $products = $this->productService->getByCategory($cat_ids, $per_page, $current_page, $sort, $filters);
        
        $total_pages = $per_page !== null ? (int)ceil($total_products / $per_page) : 1;

        $available_filters = $this->productService->getAvailableFilters($cat_ids);
        $breadcrumb = $this->categoryService->getBreadcrumb($category->id);

        $data = [
            'page_title'        => $category->name,
            'category'          => $category,
            'subcategories'     => $subcategories,
            'products'          => $products,
            'breadcrumb'        => $breadcrumb,
            'total_products'    => $total_products,
            'total_pages'       => $total_pages,
            'current_page'      => $current_page,
            'sort'              => $sort,
            'per_page_param'    => $per_page_param,
            'available_filters' => $available_filters,
            'active_filters'    => $filters,
        ];

        if ($this->isAjax()) {
            $this->renderer->renderPartial('partials/product_list', $data);
        } else {
            $this->renderer->render('category', $data);
        }
    }

    public function products() {
        $sort = in_array($_GET['sort'] ?? '', ['name', 'price_asc', 'price_desc', 'featured']) ? $_GET['sort'] : 'name';
        $per_page_raw = $_GET['per_page'] ?? '12';
        $per_page_param = $per_page_raw === 'all' ? 'all' : (in_array((int)$per_page_raw, [12, 24]) ? (string)(int)$per_page_raw : '12');
        $per_page = $per_page_param === 'all' ? null : (int)$per_page_param;
        $current_page = max(1, (int)($_GET['page'] ?? 1));

        $filters = $this->getFiltersFromRequest();

        $total_products = $this->productService->countAllActive($filters);
        $products = $this->productService->getAllActive($per_page, $current_page, $sort, $filters);
        $total_pages = $per_page !== null ? (int)ceil($total_products / $per_page) : 1;

        $available_filters = $this->productService->getAvailableFilters();

        $data = [
            'page_title'        => 'All Products',
            'products'          => $products,
            'total_products'    => $total_products,
            'total_pages'       => $total_pages,
            'current_page'      => $current_page,
            'sort'              => $sort,
            'per_page_param'    => $per_page_param,
            'available_filters' => $available_filters,
            'active_filters'    => $filters,
        ];

        if ($this->isAjax()) {
            $this->renderer->renderPartial('partials/product_list', $data);
        } else {
            $this->renderer->render('products', $data);
        }
    }

    private function getFiltersFromRequest(): array {
        return [
            'price_min'  => $_GET['price_min'] ?? null,
            'price_max'  => $_GET['price_max'] ?? null,
            'attributes' => isset($_GET['attr']) && is_array($_GET['attr']) ? array_map('intval', $_GET['attr']) : []
        ];
    }

    private function isAjax(): bool {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_GET['ajax']);
    }

    public function product($slug = '') {
        $product = $this->productService->findBySlug($slug);

        if (!$product) {
            http_response_code(404);
            exit('Product not found.');
        }

        $breadcrumb = $product->category_id ? $this->categoryService->getBreadcrumb($product->category_id) : [];

        // Track Recently Viewed
        if (!isset($_SESSION['recently_viewed'])) {
            $_SESSION['recently_viewed'] = [];
        }
        // Remove if already exists to move it to the end
        $_SESSION['recently_viewed'] = array_filter($_SESSION['recently_viewed'], fn($id) => $id != $product->id);
        array_unshift($_SESSION['recently_viewed'], $product->id);
        // Keep only last 8 (7 to show, plus 1 for current product being filtered out)
        $_SESSION['recently_viewed'] = array_slice($_SESSION['recently_viewed'], 0, 8);

        // Fetch Recently Viewed products (excluding current)
        $recent_ids = array_values(array_filter($_SESSION['recently_viewed'], fn($id) => $id != $product->id));
        $recently_viewed = !empty($recent_ids) ? $this->productService->findByIds($recent_ids) : [];
        
        // Ensure products are returned in the exact order of the history
        if (!empty($recently_viewed) && !empty($recent_ids)) {
            $id_order = array_flip($recent_ids);
            usort($recently_viewed, fn($a, $b) => $id_order[$a->id] <=> $id_order[$b->id]);
        }

        // Related products logic (Smart Weighted)
        $related_products = $this->productService->getRelatedProducts($product->id, 4);

        $this->renderer->render('product', [
            'page_title'      => $product->name,
            'product'         => $product,
            'breadcrumb'      => $breadcrumb,
            'related_products' => $related_products,
            'recently_viewed'  => $recently_viewed,
            'flash_success'   => flash('success'),
        ]);
    }

    public function handleIcon() {
        header('Location: ' . BASE_URL . '/public/images/favicon.svg');
        exit;
    }
}
