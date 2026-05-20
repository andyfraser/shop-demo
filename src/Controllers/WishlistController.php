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
        $settings = $this->wishlistService->getSettings($user->id);

        return new HtmlResponse($this->renderer->render('wishlist', [
            'page_title' => 'My Wishlist',
            'wishlist' => $wishlist,
            'settings' => $settings,
            'share_url' => $settings['share_hash'] ? $request->getFullBaseUrl() . '/wishlist/shared/' . $settings['share_hash'] : null
        ]));
    }

    public function togglePrivacy(Request $request): Response {
        if (!$this->securityService->validateCsrf($request->getPost('csrf_token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], 403);
        }

        $user = $this->authService->currentUser();
        $isPublic = (bool)$request->getPost('is_public');
        
        $settings = $this->wishlistService->togglePrivacy($user->id, $isPublic);
        $settings['share_url'] = $settings['share_hash'] ? $request->getFullBaseUrl() . '/wishlist/shared/' . $settings['share_hash'] : null;

        return new JsonResponse([
            'ok' => true,
            'is_public' => $settings['is_public'],
            'share_url' => $settings['share_url']
        ]);
    }

    public function viewShared(Request $request, $hash): Response {
        $data = $this->wishlistService->getSharedWishlist($hash);
        
        if (!$data) {
            return new HtmlResponse($this->renderer->render('404', [
                'page_title' => 'Wishlist Not Found',
                'message' => 'This wishlist does not exist or is no longer public.'
            ]), 404);
        }

        return new HtmlResponse($this->renderer->render('wishlist_shared', [
            'page_title' => $data['user']->name . "'s Wishlist",
            'wishlist' => $data['products'],
            'owner_name' => $data['user']->name
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
