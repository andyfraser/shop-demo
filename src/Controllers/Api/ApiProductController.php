<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\JsonResponse;
use App\Core\QueryCriteria;
use App\Services\ProductServiceInterface;
use App\Services\CategoryServiceInterface;
use App\Services\ReviewServiceInterface;
use App\Services\AttributeServiceInterface;
use App\Services\AuthServiceInterface;
use App\Services\ImageServiceInterface;
use App\Services\CurrencyServiceInterface;

class ApiProductController {
    public function __construct(
        private ProductServiceInterface $productService,
        private CategoryServiceInterface $categoryService,
        private ReviewServiceInterface $reviewService,
        private AttributeServiceInterface $attributeService,
        private AuthServiceInterface $authService,
        private ImageServiceInterface $imageService,
        private CurrencyServiceInterface $currencyService
    ) {}

    public function index(Request $request): Response {
        $criteria = QueryCriteria::fromRequest($request->getQuery(), 12);
        $categorySlug = $request->getQuery('category');
        
        $products = [];
        $totalProducts = 0;

        if ($categorySlug) {
            $category = $this->categoryService->findBySlug($categorySlug);
            if (!$category) {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Category not found'
                    ]
                ], 404);
            }

            // Get subcategories recursively
            $subcategories = $this->categoryService->getSubcategories($category->id);
            $catIds = [$category->id];
            $queue = array_map(fn($c) => $c->id, $subcategories);

            while ($queue) {
                $id = array_shift($queue);
                $catIds[] = $id;
                $subs = $this->categoryService->getSubcategories($id);
                foreach ($subs as $row) {
                    $queue[] = $row->id;
                }
            }

            $totalProducts = $this->productService->countByCategory($catIds, $criteria);
            $products = $this->productService->getByCategory($catIds, $criteria);
        } else {
            $totalProducts = $this->productService->countAllActive($criteria);
            $products = $this->productService->getAllActive($criteria);
        }

        $user = $this->authService->currentUser();
        $this->productService->attachActivePromotions($products, $user);

        $data = [];
        foreach ($products as $p) {
            $data[] = $this->formatProductSummary($p);
        }

        $totalPages = $criteria->getLimit() !== null ? (int)ceil($totalProducts / $criteria->getLimit()) : 1;

        return new JsonResponse([
            'success' => true,
            'data' => [
                'products' => $data,
                'currency' => [
                    'code' => $this->currencyService->getCurrentCurrency()->code,
                    'symbol' => $this->currencyService->getCurrentCurrency()->symbol
                ],
                'pagination' => [
                    'current_page' => $criteria->getPage(),
                    'total_pages' => $totalPages,
                    'total_items' => $totalProducts,
                    'limit' => $criteria->getLimit()
                ]
            ]
        ]);
    }

    public function show(Request $request, string $slug): Response {
        $product = $this->productService->findBySlug($slug);
        if (!$product) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Product not found'
                ]
            ], 404);
        }

        $user = $this->authService->currentUser();
        $this->productService->attachActivePromotions([$product], $user);

        $variants = $this->productService->getVariants($product->id);
        $attributes = $this->attributeService->getProductAttributeValuesWithDetails($product->id);
        $avgRating = $this->reviewService->getAverageRating($product->id);
        $reviews = $this->reviewService->getByProductId($product->id);

        $formattedProduct = $this->formatProductDetails($product, $variants, $attributes, $avgRating, $reviews);
        $formattedProduct['currency'] = [
            'code' => $this->currencyService->getCurrentCurrency()->code,
            'symbol' => $this->currencyService->getCurrentCurrency()->symbol
        ];

        return new JsonResponse([
            'success' => true,
            'data' => $formattedProduct
        ]);
    }

    public function related(Request $request, string $slug): Response {
        $product = $this->productService->findBySlug($slug);
        if (!$product) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Product not found'
                ]
            ], 404);
        }

        $related = $this->productService->getRelatedProducts($product->id, 4);
        $user = $this->authService->currentUser();
        $this->productService->attachActivePromotions($related, $user);

        $data = [];
        foreach ($related as $p) {
            $data[] = $this->formatProductSummary($p);
        }

        return new JsonResponse([
            'success' => true,
            'data' => $data,
            'currency' => [
                'code' => $this->currencyService->getCurrentCurrency()->code,
                'symbol' => $this->currencyService->getCurrentCurrency()->symbol
            ]
        ]);
    }

    public function categories(Request $request): Response {
        $tree = $this->categoryService->getTree();
        return new JsonResponse([
            'success' => true,
            'data' => $tree
        ]);
    }

    public function submitReview(Request $request, string $slug): Response {
        $product = $this->productService->findBySlug($slug);
        if (!$product) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Product not found'
                ]
            ], 404);
        }

        $user = $this->authService->currentUser();
        $rating = (int)$request->getPost('rating', 0);
        $comment = trim($request->getPost('comment', ''));

        if ($rating < 1 || $rating > 5) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Please provide a rating between 1 and 5.'
                ]
            ], 400);
        }

        $this->reviewService->submit($product->id, $user->id, $rating, $comment);

        return new JsonResponse([
            'success' => true,
            'data' => [
                'message' => 'Your review has been submitted and is awaiting moderation.'
            ]
        ], 201);
    }

    private function formatProductSummary(\App\Models\Product $p): array {
        $promos = [];
        foreach ($p->active_promotions as $promo) {
            $promos[] = [
                'name' => $promo->name,
                'code' => $promo->code,
                'type' => $promo->type,
                'discount_type' => $promo->type,
                'value' => $promo->value,
                'buy_qty' => $promo->buy_qty,
                'get_qty' => $promo->get_qty,
                'min_order_amount' => $promo->min_order_amount,
                'description' => $promo->description
            ];
        }

        return [
            'id' => $p->id,
            'name' => $p->name,
            'slug' => $p->slug,
            'sku' => $p->sku,
            'price' => $this->currencyService->convert((float)$p->price),
            'stock' => $p->stock,
            'image' => $this->imageService->getUrl($p->image, 'original'),
            'image_thumb' => $this->imageService->getUrl($p->image, 'thumb'),
            'image_medium' => $this->imageService->getUrl($p->image, 'medium'),
            'image_large' => $this->imageService->getUrl($p->image, 'large'),
            'featured' => (bool)$p->featured,
            'is_new' => (bool)$p->isNew(),
            'is_purchasable' => (bool)$p->is_purchasable,
            'force_variant' => (bool)$p->force_variant,
            'active_promotions' => $promos
        ];
    }

    private function formatProductDetails(\App\Models\Product $p, array $variants, array $attributes, float $avgRating, array $reviews): array {
        $formattedVariants = [];
        foreach ($variants as $v) {
            $formattedVariants[] = [
                'id' => $v->id,
                'sku' => $v->sku,
                'name' => $v->name,
                'price_modifier' => $this->currencyService->convert((float)$v->price_modifier),
                'stock' => $v->stock
            ];
        }

        $formattedAttributes = [];
        foreach ($attributes as $a) {
            $formattedAttributes[] = [
                'attribute_id' => $a['attribute_id'],
                'attribute_name' => $a['attribute_name'],
                'value_id' => $a['id'],
                'value' => $a['value']
            ];
        }

        $formattedReviews = [];
        foreach ($reviews as $r) {
            $formattedReviews[] = [
                'id' => $r->id,
                'user_name' => $r->user_name ?? 'Anonymous',
                'rating' => $r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at
            ];
        }

        $details = $this->formatProductSummary($p);
        $details['description'] = $p->description;
        $details['is_bundle'] = (bool)$p->is_bundle;
        $details['is_virtual'] = (bool)$p->is_virtual;
        $details['average_rating'] = $avgRating;
        $details['variants'] = $formattedVariants;
        $details['attributes'] = $formattedAttributes;
        $details['reviews'] = $formattedReviews;

        // Map quantity discount tiers if loaded
        $details['tiers'] = [];
        if (!empty($p->tiers)) {
            foreach ($p->tiers as $t) {
                $details['tiers'][] = [
                    'min_qty' => (int)$t['min_qty'],
                    'discount' => $this->currencyService->convert((float)$t['discount'])
                ];
            }
        }

        // Map bundle components if loaded
        $details['bundle_items'] = [];
        if ($p->is_bundle && !empty($p->bundle_items)) {
            foreach ($p->bundle_items as $item) {
                $details['bundle_items'][] = [
                    'product_id' => (int)$item['product_id'],
                    'name' => $item['name'] ?? '',
                    'sku' => $item['sku'] ?? '',
                    'price' => $this->currencyService->convert((float)($item['price'] ?? 0.0)),
                    'quantity' => (int)$item['qty']
                ];
            }
        }

        return $details;
    }
}
