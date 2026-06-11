<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\JsonResponse;
use App\Services\WishlistServiceInterface;
use App\Services\AuthServiceInterface;
use App\Services\ProductServiceInterface;

class ApiWishlistController {
    public function __construct(
        private WishlistServiceInterface $wishlistService,
        private AuthServiceInterface $authService,
        private ProductServiceInterface $productService
    ) {}

    public function index(Request $request): Response {
        $user = $this->authService->currentUser();
        $wishlist = $this->wishlistService->getUserWishlist($user->id);
        $settings = $this->wishlistService->getSettings($user->id);

        $formattedProducts = [];
        foreach ($wishlist as $p) {
            $formattedProducts[] = [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'sku' => $p->sku,
                'price' => $p->price,
                'stock' => $p->stock,
                'image' => $p->image,
                'is_purchasable' => (bool)$p->is_purchasable
            ];
        }

        $shareUrl = $settings['share_hash'] ? $request->getFullBaseUrl() . '/wishlist/shared/' . $settings['share_hash'] : null;

        return new JsonResponse([
            'success' => true,
            'data' => [
                'items' => $formattedProducts,
                'is_public' => (bool)$settings['is_public'],
                'share_url' => $shareUrl
            ]
        ]);
    }

    public function add(Request $request, string $productId): Response {
        $user = $this->authService->currentUser();
        $pid = (int)$productId;

        $product = $this->productService->findById($pid);
        if (!$product) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Product not found.'
                ]
            ], 404);
        }

        $this->wishlistService->addToWishlist($user->id, $pid);

        return new JsonResponse([
            'success' => true,
            'message' => 'Product added to wishlist.'
        ], 201);
    }

    public function remove(Request $request, string $productId): Response {
        $user = $this->authService->currentUser();
        $pid = (int)$productId;

        $this->wishlistService->removeFromWishlist($user->id, $pid);

        return new JsonResponse([
            'success' => true,
            'message' => 'Product removed from wishlist.'
        ]);
    }

    public function togglePrivacy(Request $request): Response {
        $user = $this->authService->currentUser();
        $isPublic = (bool)$request->getPost('is_public');

        $settings = $this->wishlistService->togglePrivacy($user->id, $isPublic);
        $shareUrl = $settings['share_hash'] ? $request->getFullBaseUrl() . '/wishlist/shared/' . $settings['share_hash'] : null;

        return new JsonResponse([
            'success' => true,
            'data' => [
                'is_public' => (bool)$settings['is_public'],
                'share_url' => $shareUrl
            ]
        ]);
    }
}
