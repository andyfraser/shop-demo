<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\ReviewServiceInterface;
use App\Services\SecurityServiceInterface;

class AdminReviewsController {
    public function __construct(
        private ReviewServiceInterface $reviewService,
        private Renderer $renderer,
        private SecurityServiceInterface $security
    ) {}

    public function list() {
        $reviews = $this->reviewService->getAllForAdmin();
        $this->renderer->adminRender('reviews_list', [
            'page_title' => 'Product Reviews',
            'active'     => 'reviews',
            'reviews'    => $reviews,
            'flash_msg'  => flash('msg'),
        ]);
    }

    public function updateStatus() {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'pending';

        if ($id && in_array($status, ['approved', 'rejected', 'pending'])) {
            $this->reviewService->updateStatus($id, $status);
            flash('msg', 'Review ' . ucfirst($status) . '.');
        }
        redirect('/admin/reviews');
    }
}
