<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\WishlistServiceInterface;
use App\Services\AuthServiceInterface;
use App\Services\SecurityServiceInterface;

class WishlistController {
    public function __construct(
        private Renderer $renderer,
        private WishlistServiceInterface $wishlistService,
        private AuthServiceInterface $authService,
        private SecurityServiceInterface $securityService
    ) {}

    public function index() {
        $user = $this->authService->currentUser();
        if (!$user) {
            redirect('/login');
        }

        $wishlist = $this->wishlistService->getUserWishlist($user->id);

        $this->renderer->render('wishlist', [
            'page_title' => 'My Wishlist',
            'wishlist' => $wishlist,
        ]);
    }

    public function add($productId) {
        $this->securityService->verifyCsrf();
        
        $user = $this->authService->currentUser();
        if (!$user) {
            flash('error', 'You must be logged in to add items to your wishlist.');
            redirect('/login');
        }

        $this->wishlistService->addToWishlist($user->id, (int)$productId);

        if (is_ajax()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'message' => 'Added to wishlist.']);
            exit;
        }

        flash('success', 'Product added to your wishlist.');
        $redirect = $_SERVER['HTTP_REFERER'] ?? '/wishlist';
        redirect($redirect);
    }

    public function remove($productId) {
        $this->securityService->verifyCsrf();

        $user = $this->authService->currentUser();
        if (!$user) {
            redirect('/login');
        }

        $this->wishlistService->removeFromWishlist($user->id, (int)$productId);

        if (is_ajax()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'message' => 'Removed from wishlist.']);
            exit;
        }

        flash('success', 'Product removed from your wishlist.');
        redirect('/wishlist');
    }
}
