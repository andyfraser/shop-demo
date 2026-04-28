<?php

namespace App\Services;

use App\Models\Product;

interface WishlistServiceInterface {
    /**
     * Get all products in a user's wishlist.
     * @return Product[]
     */
    public function getUserWishlist(int $userId): array;

    /**
     * Add a product to a user's wishlist.
     */
    public function addToWishlist(int $userId, int $productId): bool;

    /**
     * Remove a product from a user's wishlist.
     */
    public function removeFromWishlist(int $userId, int $productId): bool;

    /**
     * Check if a product is in a user's wishlist.
     */
    public function isInWishlist(int $userId, int $productId): bool;
}
