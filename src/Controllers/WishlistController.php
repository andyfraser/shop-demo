<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\JsonResponse;
use App\Core\Responses\RedirectResponse;
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

    public function index(Request $request): Response {
        $user = $this->authService->currentUser();
        $wishlist = $this->wishlistService->getUserWishlist($user->id);

        return new HtmlResponse($this->renderer->render('wishlist', [
            'page_title' => 'My Wishlist',
            'wishlist' => $wishlist,
        ]));
    }

    public function add(Request $request, $productId): Response {
        $user = $this->authService->currentUser();
        $this->wishlistService->addToWishlist($user->id, (int)$productId);

        if ($request->isAjax()) {
            return new JsonResponse(['ok' => true, 'message' => 'Added to wishlist.']);
        }

        flash('success', 'Product added to your wishlist.');
        $redirect = $request->getServer('HTTP_REFERER', '/wishlist');
        return new RedirectResponse($redirect);
    }

    public function remove(Request $request, $productId): Response {
        $user = $this->authService->currentUser();
        $this->wishlistService->removeFromWishlist($user->id, (int)$productId);

        if ($request->isAjax()) {
            return new JsonResponse(['ok' => true, 'message' => 'Removed from wishlist.']);
        }

        flash('success', 'Product removed from your wishlist.');
        return new RedirectResponse('/wishlist');
    }
}
