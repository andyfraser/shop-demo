<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\JsonResponse;
use App\Core\Responses\RedirectResponse;
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

    public function index(Request $request): Response {
        $featured_products = $this->productService->getFeatured(8);
        $this->productService->attachActivePromotions($featured_products, $this->authService->currentUser());

        return new HtmlResponse($this->renderer->render('home', [
            'page_title'       => 'Welcome',
            'featured_products' => $featured_products,
        ]));
    }

    public function suggestions(Request $request): Response {
        $query = trim($request->getQuery('q', ''));
        if (mb_strlen($query) < 3) {
            return new JsonResponse([]);
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

        return new JsonResponse($suggestions);
    }

    public function search(Request $request): Response {
        $criteria = \App\Core\QueryCriteria::fromRequest($request->getQuery(), 12);
        $query = $criteria->getSearchTerm();

        $products = [];
        $total_products = 0;
        $total_pages    = 1;

        if ($query) {
            $total_products = $this->productService->countSearch($criteria);
            $products = $this->productService->search($criteria);
            $this->productService->attachActivePromotions($products, $this->authService->currentUser());
            
            if ($criteria->getLimit() !== null) {
                $total_pages = (int)ceil($total_products / $criteria->getLimit());
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
            'current_page'      => $criteria->getPage(),
            'sort'              => $criteria->getSort() ?: 'name',
            'per_page_param'    => $request->getQuery('per_page', '12'),
            'available_filters' => $available_filters,
            'active_filters'    => $criteria->getFilters(),
        ];

        if ($request->isAjax()) {
            return new HtmlResponse($this->renderer->renderPartial('partials/product_list', $data));
        } else {
            return new HtmlResponse($this->renderer->render('search', $data));
        }
    }

    public function category(Request $request, $slug = ''): Response {
        $category = $this->categoryService->findBySlug($slug);

        if (!$category) {
            return new HtmlResponse($this->renderer->render('404', ['page_title' => 'Category Not Found']), 404);
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

        $criteria = \App\Core\QueryCriteria::fromRequest($request->getQuery(), 12);

        $total_products = $this->productService->countByCategory($cat_ids, $criteria);
        $products = $this->productService->getByCategory($cat_ids, $criteria);
        $this->productService->attachActivePromotions($products, $this->authService->currentUser());
        
        $total_pages = $criteria->getLimit() !== null ? (int)ceil($total_products / $criteria->getLimit()) : 1;

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
            'current_page'      => $criteria->getPage(),
            'sort'              => $criteria->getSort() ?: 'name',
            'per_page_param'    => $request->getQuery('per_page', '12'),
            'available_filters' => $available_filters,
            'active_filters'    => $criteria->getFilters(),
        ];

        if ($request->isAjax()) {
            return new HtmlResponse($this->renderer->renderPartial('partials/product_list', $data));
        } else {
            return new HtmlResponse($this->renderer->render('category', $data));
        }
    }

    public function promotion(Request $request, $code): Response {
        $promo = $this->promotionService->findByCode($code);

        if (!$promo) {
            flash('error', 'This promo code does not exist.');
            return new HtmlResponse($this->renderer->render('404', ['page_title' => 'Promotion Not Found']), 404);
        }

        $user = $this->authService->currentUser();
        $isFirstOrder = $user ? !$this->orderService->hasOrders($user->id) : false;

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
            return new RedirectResponse('/');
        }


        // Apply promo code automatically
        $this->cartService->applyPromoCode($code);

        // 1. If targets 1 category -> go to category page
        if ($promo->target_type === \App\Models\Promotion::TARGET_CATEGORY && count($promo->target_ids) === 1) {
            $category = $this->categoryService->findById($promo->target_ids[0]);
            if ($category) {
                return new RedirectResponse('/category/' . $category->slug);
            }
        }

        // 2. If targets 1 product -> go to product page
        if ($promo->target_type === \App\Models\Promotion::TARGET_PRODUCT && count($promo->target_ids) === 1) {
            $product = $this->productService->findById($promo->target_ids[0]);
            if ($product) {
                return new RedirectResponse('/product/' . $product->slug);
            }
        }

        // 3. Otherwise show a list of qualifying products
        return $this->promotionProducts($request, $promo);
    }

    private function promotionProducts(Request $request, \App\Models\Promotion $promo): Response {
        $criteria = \App\Core\QueryCriteria::fromRequest($request->getQuery(), 12);
        $cat_ids = [];

        // We need to fetch all potentially qualifying products to count them correctly
        // because isProductQualifying can have complex logic (exclusions, etc.)
        $all_products = [];
        
        // Temporarily remove limit to get all for in-memory qualification check
        $fullCriteria = clone $criteria;
        $fullCriteria->withLimit(null)->withPage(1);

        if ($promo->target_type === \App\Models\Promotion::TARGET_CATEGORY) {
            if (empty($promo->target_ids)) {
                $all_products = $this->productService->getAllActive($fullCriteria);
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
                $all_products = $this->productService->getByCategory($expanded_cat_ids, $fullCriteria);
            }
        } elseif ($promo->target_type === \App\Models\Promotion::TARGET_PRODUCT) {
            $fullCriteria->addFilter('product_ids', $promo->target_ids);
            $all_products = $this->productService->getAllActive($fullCriteria);
        } else {
            $all_products = $this->productService->getAllActive($fullCriteria);
        }

        // Filter all products by qualification
        $qualifying_products = array_filter($all_products, fn($p) => $this->promotionService->isProductQualifying($p, $promo));
        $total_products = count($qualifying_products);

        // Paginate the qualifying products in memory (since they are already fetched)
        if ($criteria->getLimit() === null) {
            $products = $qualifying_products;
            $total_pages = 1;
        } else {
            $total_pages = (int)ceil($total_products / $criteria->getLimit());
            $offset = $criteria->getOffset();
            $products = array_slice($qualifying_products, $offset, $criteria->getLimit());
        }

        $this->productService->attachActivePromotions($products, $this->authService->currentUser());

        $available_filters = $this->productService->getAvailableFilters($cat_ids);

        $data = [
            'page_title'        => 'Promotion: ' . $promo->name,
            'promotion'         => $promo,
            'products'          => $products,
            'total_products'    => $total_products,
            'total_pages'       => $total_pages,
            'current_page'      => $criteria->getPage(),
            'sort'              => $criteria->getSort() ?: 'name',
            'per_page_param'    => $request->getQuery('per_page', '12'),
            'available_filters' => $available_filters,
            'active_filters'    => $criteria->getFilters(),
        ];

        if ($request->isAjax()) {
            return new HtmlResponse($this->renderer->renderPartial('partials/product_list', $data));
        } else {
            return new HtmlResponse($this->renderer->render('products', $data));
        }
    }

    public function products(Request $request): Response {
        $criteria = \App\Core\QueryCriteria::fromRequest($request->getQuery(), 12);

        $total_products = $this->productService->countAllActive($criteria);
        $products = $this->productService->getAllActive($criteria);
        $this->productService->attachActivePromotions($products, $this->authService->currentUser());
        $total_pages = $criteria->getLimit() !== null ? (int)ceil($total_products / $criteria->getLimit()) : 1;

        $available_filters = $this->productService->getAvailableFilters();

        $data = [
            'page_title'        => 'All Products',
            'products'          => $products,
            'total_products'    => $total_products,
            'total_pages'       => $total_pages,
            'current_page'      => $criteria->getPage(),
            'sort'              => $criteria->getSort() ?: 'name',
            'per_page_param'    => $request->getQuery('per_page', '12'),
            'available_filters' => $available_filters,
            'active_filters'    => $criteria->getFilters(),
        ];

        if ($request->isAjax()) {
            return new HtmlResponse($this->renderer->renderPartial('partials/product_list', $data));
        } else {
            return new HtmlResponse($this->renderer->render('products', $data));
        }
    }

    public function product(Request $request, $slug = ''): Response {
        $product = $this->productService->findBySlug($slug);

        if (!$product) {
            return new HtmlResponse($this->renderer->render('404', ['page_title' => 'Product Not Found']), 404);
        }

        $this->productService->attachActivePromotions([$product], $this->authService->currentUser());

        $breadcrumb = $product->category_id ? $this->categoryService->getBreadcrumb($product->category_id) : [];

        // Track Recently Viewed
        $session = $request->getSession();
        $recently_viewed_ids = $session['recently_viewed'] ?? [];
        
        // Remove if already exists to move it to the end
        $recently_viewed_ids = array_filter($recently_viewed_ids, fn($id) => $id != $product->id);
        array_unshift($recently_viewed_ids, $product->id);
        // Keep only last 8
        $recently_viewed_ids = array_slice($recently_viewed_ids, 0, 8);
        
        $_SESSION['recently_viewed'] = $recently_viewed_ids;

        // Fetch Recently Viewed products (excluding current)
        $recent_ids = array_values(array_filter($recently_viewed_ids, fn($id) => $id != $product->id));
        $recently_viewed = !empty($recent_ids) ? $this->productService->findByIds($recent_ids) : [];
        
        // Ensure products are returned in the exact order of the history
        if (!empty($recently_viewed) && !empty($recent_ids)) {
            $id_order = array_flip($recent_ids);
            usort($recently_viewed, fn($a, $b) => $id_order[$a->id] <=> $id_order[$b->id]);
        }

        // Related products logic (Smart Weighted)
        $related_products = $this->productService->getRelatedProducts($product->id, 4);
        $this->productService->attachActivePromotions($related_products, $this->authService->currentUser());
        $this->productService->attachActivePromotions($recently_viewed, $this->authService->currentUser());

        $is_in_wishlist = false;
        $user = $this->authService->currentUser();
        if ($user) {
            $is_in_wishlist = $this->wishlistService->isInWishlist($user->id, $product->id);
        }

        $reviews = $this->reviewService->getByProductId($product->id);
        $avg_rating = $this->reviewService->getAverageRating($product->id);

        return new HtmlResponse($this->renderer->render('product', [
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
        ]));
    }

    public function submitReview(Request $request, $slug): Response {
        $product = $this->productService->findBySlug($slug);
        if (!$product) return new RedirectResponse('/');

        $user = $this->authService->currentUser();

        $rating = (int)$request->getPost('rating', 0);
        $comment = trim($request->getPost('comment', ''));

        if ($rating < 1 || $rating > 5) {
            flash('error', 'Please provide a rating between 1 and 5.');
            return new RedirectResponse('/product/' . $slug);
        }

        $this->reviewService->submit($product->id, $user->id, $rating, $comment);
        flash('success', 'Your review has been submitted and is awaiting moderation.');
        return new RedirectResponse('/product/' . $slug);
    }

    public function handleIcon(Request $request): Response {
        return new RedirectResponse(BASE_URL . '/images/favicon.svg');
    }
}
