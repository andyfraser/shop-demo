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

        if ($query) {
            $total_products = $this->productService->countSearch($query);
            $products = $this->productService->search($query, $per_page, $current_page, $sort);
            
            if ($per_page !== null) {
                $total_pages = (int)ceil($total_products / $per_page);
            }
        }

        $this->renderer->render('search', [
            'page_title'     => $query ? 'Search: ' . $query : 'Search',
            'search_query'   => $query,
            'query'          => $query,
            'products'       => $products,
            'total_products' => $total_products,
            'total_pages'    => $total_pages,
            'current_page'   => $current_page,
            'sort'           => $sort,
            'per_page_param' => $per_page_param,
        ]);
    }

    public function category($slug = '') {
        $category = $this->categoryService->findBySlug($slug);

        if (!$category) {
            http_response_code(404);
            exit('Category not found.');
        }

        $subcategories = $this->categoryService->getSubcategories($category->id);

        // Collect all descendant category IDs to include products from subcategories
        $cat_ids = [$category->id];
        $queue = array_column($subcategories, 'id');
        // If array_column doesn't work on objects directly in all PHP versions, 
        // we might need to map it, but it should work for public properties.
        if (empty($queue) && !empty($subcategories)) {
            $queue = array_map(fn($c) => $c->id, $subcategories);
        }

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

        $total_products = $this->productService->countByCategory($cat_ids);
        $products = $this->productService->getByCategory($cat_ids, $per_page, $current_page, $sort);
        
        $total_pages = $per_page !== null ? (int)ceil($total_products / $per_page) : 1;

        $breadcrumb = get_breadcrumb($category->id);

        $this->renderer->render('category', [
            'page_title'     => $category->name,
            'category'       => $category,
            'subcategories'  => $subcategories,
            'products'       => $products,
            'breadcrumb'     => $breadcrumb,
            'total_products' => $total_products,
            'total_pages'    => $total_pages,
            'current_page'   => $current_page,
            'sort'           => $sort,
            'per_page_param' => $per_page_param,
        ]);
    }

    public function products() {
        $sort = in_array($_GET['sort'] ?? '', ['name', 'price_asc', 'price_desc', 'featured']) ? $_GET['sort'] : 'name';
        $per_page_raw = $_GET['per_page'] ?? '12';
        $per_page_param = $per_page_raw === 'all' ? 'all' : (in_array((int)$per_page_raw, [12, 24]) ? (string)(int)$per_page_raw : '12');
        $per_page = $per_page_param === 'all' ? null : (int)$per_page_param;
        $current_page = max(1, (int)($_GET['page'] ?? 1));

        $total_products = $this->productService->countAllActive();
        $products = $this->productService->getAllActive($per_page, $current_page, $sort);
        $total_pages = $per_page !== null ? (int)ceil($total_products / $per_page) : 1;

        $this->renderer->render('products', [
            'page_title'     => 'All Products',
            'products'       => $products,
            'total_products' => $total_products,
            'total_pages'    => $total_pages,
            'current_page'   => $current_page,
            'sort'           => $sort,
            'per_page_param' => $per_page_param,
        ]);
    }

    public function product($slug = '') {
        $product = $this->productService->findBySlug($slug);

        if (!$product) {
            http_response_code(404);
            exit('Product not found.');
        }

        $breadcrumb = $product->category_id ? get_breadcrumb($product->category_id) : [];

        // Related products logic
        $related_products = $this->productService->getByCategory([$product->category_id], 4, 1, 'featured');
        // Filter out current product
        $related_products = array_filter($related_products, fn($p) => $p->id != $product->id);

        $this->renderer->render('product', [
            'page_title'      => $product->name,
            'product'         => $product,
            'breadcrumb'      => $breadcrumb,
            'related_products' => $related_products,
            'flash_success'   => flash('success'),
        ]);
    }

    public function handleIcon() {
        header('Location: ' . BASE_URL . '/public/images/favicon.svg');
        exit;
    }
}
