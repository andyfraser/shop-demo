<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Renderer;
use App\Services\ReviewServiceInterface;
use App\Services\SecurityServiceInterface;

class AdminReviewsController {
    public function __construct(
        private ReviewServiceInterface $reviewService,
        private Renderer $renderer,
        private SecurityServiceInterface $security
    ) {}

    public function list(Request $request): Response {
        $reviews = $this->reviewService->getAllForAdmin();
        return new HtmlResponse($this->renderer->adminRender('reviews_list', [
            'page_title' => 'Product Reviews',
            'active'     => 'reviews',
            'reviews'    => $reviews,
            'flash_msg'  => flash('msg'),
        ]));
    }

    public function updateStatus(Request $request): Response {
        $id = (int)$request->getPost('id', 0);
        $status = $request->getPost('status', 'pending');

        if ($id && in_array($status, ['approved', 'rejected', 'pending'])) {
            $this->reviewService->updateStatus($id, $status);
            flash('msg', 'Review ' . ucfirst($status) . '.');
        }
        return new RedirectResponse('/admin/reviews');
    }
}
