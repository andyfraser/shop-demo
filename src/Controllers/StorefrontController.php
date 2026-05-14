<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\ProductServiceInterface;
use App\Services\CategoryServiceInterface;
use App\Services\WishlistServiceInterface;
use App\Services\AuthServiceInterface;
use App\Services\ReviewServiceInterface;
use App\Services\PromotionServiceInterface;
use App\Services\CartServiceInterface;

class StorefrontController {
    public function __construct(
        private ProductServiceInterface $productService,
        private CategoryServiceInterface $categoryService,
        private WishlistServiceInterface $wishlistService,
        private AuthServiceInterface $authService,
        private ReviewServiceInterface $reviewService,
        private PromotionServiceInterface $promotionService,
        private CartServiceInterface $cartService,
        private \App\Services\OrderServiceInterface $orderService,
        private Renderer $renderer
    ) {}

    public function index() {
        $featured_products = $this->productService->getFeatured(8);
        $this->productService->attachActivePromotions($featured_products);

        $this->renderer->render('home', [
            'page_title'       => 'Welcome',
            'featured_products' => $featured_products,
        ]);
    }

    public function suggestions() {
        $query = trim($_GET['q'] ?? '');
        if (mb_strlen($query) < 3) {
            header('Content-Type: application/json');
            echo json_encode([]);
            return;
        }

        $products = $this->productService->searchSuggestions($query, 5);
        
        $suggestions = [];
        foreach ($products as $p) {
            $suggestions[] = [
                'id'    => $p->id,
                'name'  => $p->name,
                'slug'  => $p->slug,
                'price' => money($p->price),
                'image' => product_img_url($p->image, 'small'),
                'url'   => BASE_URL . '/product/' . $p->slug
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($suggestions);
    }

    public function search() {
        $query    = trim($_GET['q'] ?? '');
        $products = [];
        $total_products = 0;
        $total_pages    = 1;
        $current_page   = max(1, (int)($_GET['page'] ?? 1));

        $sort = in_array($_GET['sort'] ?? '', ['name', 'price_asc', 'price_desc', 'featured']) ? $_GET['sort'] : 'name';
        $per_page_raw = $_GET['per_page'] ?? '12';
        $per_page_param = $per_page_raw === 'all' ? 'all' : (in_array((int)$per_page_raw, [12, 24, 48]) ? (string)(int)$per_page_raw : '12');
        $per_page = $per_page_param === 'all' ? null : (int)$per_page_param;

        $filters = $this->getFiltersFromRequest();

        if ($query) {
            $total_products = $this->productService->countSearch($query, $filters);
            $products = $this->productService->search($query, $per_page, $current_page, $sort, $filters);
            $this->productService->attachActivePromotions($products);
            
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
        $per_page_param = $per_page_raw === 'all' ? 'all' : (in_array((int)$per_page_raw, [12, 24, 48]) ? (string)(int)$per_page_raw : '12');
        $per_page = $per_page_param === 'all' ? null : (int)$per_page_param;
        $current_page = max(1, (int)($_GET['page'] ?? 1));

        $filters = $this->getFiltersFromRequest();

        $total_products = $this->productService->countByCategory($cat_ids, $filters);
        $products = $this->productService->getByCategory($cat_ids, $per_page, $current_page, $sort, $filters);
        $this->productService->attachActivePromotions($products);
        
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

    public function promotion($code) {
        $promo = $this->promotionService->findByCode($code);

        if (!$promo) {
            http_response_code(404);
            flash('error', 'This promo code does not exist.');
            $this->renderer->render('404', ['page_title' => 'Promotion Not Found']);
            exit;
        }

        $user = $this->authService->currentUser();
        $isFirstOrder = $user ? !$this->orderService->hasOrders($user->id) : true;

        if (!$promo->isActive($user, $isFirstOrder)) {
            $now = time();
            $msg = 'This promotion is no longer active.';

            if (!$promo->active) {
                $msg = 'This promotion is currently disabled.';
            } elseif ($promo->start_date && strtotime($promo->start_date) > $now) {
                $msg = 'This promotion hasn\'t started yet.';
            } elseif ($promo->end_date && strtotime($promo->end_date) < $now) {
                $msg = 'This promotion has expired.';
            } elseif ($promo->usage_limit !== null && $promo->used_count >= $promo->usage_limit) {
                $msg = 'This promotion has reached its usage limit.';
            } elseif ($promo->target_role === \App\Models\Promotion::ROLE_FIRST_TIME && !$isFirstOrder) {
                $msg = 'This promotion is only available for your first order.';
            }

            flash('error', $msg);
            redirect('/');
        }


        // Apply promo code automatically
        $this->cartService->applyPromoCode($code);

        // 1. If targets 1 category -> go to category page
        if ($promo->target_type === \App\Models\Promotion::TARGET_CATEGORY && count($promo->target_ids) === 1) {
            $category = $this->categoryService->findById($promo->target_ids[0]);
            if ($category) {
                redirect('/category/' . $category->slug);
            }
        }

        // 2. If targets 1 product -> go to product page
        if ($promo->target_type === \App\Models\Promotion::TARGET_PRODUCT && count($promo->target_ids) === 1) {
            $product = $this->productService->findById($promo->target_ids[0]);
            if ($product) {
                redirect('/product/' . $product->slug);
            }
        }

        // 3. Otherwise show a list of qualifying products
        $this->promotionProducts($promo);
    }

    private function promotionProducts(\App\Models\Promotion $promo) {
        $sort = in_array($_GET['sort'] ?? '', ['name', 'price_asc', 'price_desc', 'featured']) ? $_GET['sort'] : 'name';
        $per_page_raw = $_GET['per_page'] ?? '12';
        $per_page_param = $per_page_raw === 'all' ? 'all' : (in_array((int)$per_page_raw, [12, 24, 48]) ? (string)(int)$per_page_raw : '12');
        $per_page = $per_page_param === 'all' ? null : (int)$per_page_param;
        $current_page = max(1, (int)($_GET['page'] ?? 1));

        $filters = $this->getFiltersFromRequest();
        $cat_ids = [];

        // We need to fetch all potentially qualifying products to count them correctly
        // because isProductQualifying can have complex logic (exclusions, etc.)
        $all_products = [];
        if ($promo->target_type === \App\Models\Promotion::TARGET_CATEGORY) {
            if (empty($promo->target_ids)) {
                $all_products = $this->productService->getAllActive(null, 1, $sort, $filters);
            } else {
                $cat_ids = $promo->target_ids;
                $expanded_cat_ids = $cat_ids;
                $queue = $cat_ids;
                while ($queue) {
                    $id = array_shift($queue);
                    $subs = $this->categoryService->getSubcategories($id);
                    foreach ($subs as $sub) {
                        if (!in_array($sub->id, $expanded_cat_ids)) {
                            $expanded_cat_ids[] = $sub->id;
                            $queue[] = $sub->id;
                        }
                    }
                }
                $all_products = $this->productService->getByCategory($expanded_cat_ids, null, 1, $sort, $filters);
            }
        } elseif ($promo->target_type === \App\Models\Promotion::TARGET_PRODUCT) {
            $filters['product_ids'] = $promo->target_ids;
            $all_products = $this->productService->getAllActive(null, 1, $sort, $filters);
        } else {
            $all_products = $this->productService->getAllActive(null, 1, $sort, $filters);
        }

        // Filter all products by qualification
        $qualifying_products = array_filter($all_products, fn($p) => $this->promotionService->isProductQualifying($p, $promo));
        $total_products = count($qualifying_products);

        // Paginate the qualifying products in memory (since they are already fetched)
        if ($per_page === null) {
            $products = $qualifying_products;
            $total_pages = 1;
        } else {
            $total_pages = (int)ceil($total_products / $per_page);
            $offset = ($current_page - 1) * $per_page;
            $products = array_slice($qualifying_products, $offset, $per_page);
        }

        $this->productService->attachActivePromotions($products);

        $available_filters = $this->productService->getAvailableFilters($cat_ids);

        $data = [
            'page_title'        => 'Promotion: ' . $promo->name,
            'promotion'         => $promo,
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
            // We can reuse the products template if it's generic enough,
            // or create a new one. The products.php template seems okay.
            $this->renderer->render('products', $data);
        }
    }

    public function products() {
        $sort = in_array($_GET['sort'] ?? '', ['name', 'price_asc', 'price_desc', 'featured']) ? $_GET['sort'] : 'name';
        $per_page_raw = $_GET['per_page'] ?? '12';
        $per_page_param = $per_page_raw === 'all' ? 'all' : (in_array((int)$per_page_raw, [12, 24, 48]) ? (string)(int)$per_page_raw : '12');
        $per_page = $per_page_param === 'all' ? null : (int)$per_page_param;
        $current_page = max(1, (int)($_GET['page'] ?? 1));

        $filters = $this->getFiltersFromRequest();

        $total_products = $this->productService->countAllActive($filters);
        $products = $this->productService->getAllActive($per_page, $current_page, $sort, $filters);
        $this->productService->attachActivePromotions($products);
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

        $this->productService->attachActivePromotions([$product]);

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
        $this->productService->attachActivePromotions($related_products);
        $this->productService->attachActivePromotions($recently_viewed);

        $is_in_wishlist = false;
        $user = $this->authService->currentUser();
        if ($user) {
            $is_in_wishlist = $this->wishlistService->isInWishlist($user->id, $product->id);
        }

        $reviews = $this->reviewService->getByProductId($product->id);
        $avg_rating = $this->reviewService->getAverageRating($product->id);

        $this->renderer->render('product', [
            'page_title'      => $product->name,
            'product'         => $product,
            'breadcrumb'      => $breadcrumb,
            'related_products' => $related_products,
            'recently_viewed'  => $recently_viewed,
            'is_in_wishlist'   => $is_in_wishlist,
            'is_logged_in'    => (bool)$user,
            'reviews'         => $reviews,
            'avg_rating'      => $avg_rating,
            'flash_success'   => flash('success'),
            'flash_error'     => flash('error'),
        ]);
    }

    public function submitReview($slug) {
        $product = $this->productService->findBySlug($slug);
        if (!$product) redirect('/');

        $user = $this->authService->currentUser();

        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            flash('error', 'Please provide a rating between 1 and 5.');
            redirect('/product/' . $slug);
        }

        $this->reviewService->submit($product->id, $user->id, $rating, $comment);
        flash('success', 'Your review has been submitted and is awaiting moderation.');
        redirect('/product/' . $slug);
    }

    public function handleIcon() {
        header('Location: ' . BASE_URL . '/public/images/favicon.svg');
        exit;
    }
}
