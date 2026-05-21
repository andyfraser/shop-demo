<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Responses\FileResponse;
use App\Core\Renderer;
use App\Services\VirtualProductServiceInterface;
use App\Services\AuthServiceInterface;
use App\Repositories\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class DownloadController {
    public function __construct(
        private Renderer $renderer,
        private VirtualProductServiceInterface $virtualProductService,
        private ProductRepositoryInterface $productRepository,
        private AuthServiceInterface $authService,
        private LoggerInterface $logger
    ) {}

    public function download(Request $request, string $token): Response {
        $download = $this->virtualProductService->verifyDownloadToken($token);

        if (!$download) {
            return new HtmlResponse($this->renderer->render('404', ['page_title' => 'Download Not Found']), 404);
        }

        // Validate expiration
        if ($download->expires_at !== null && strtotime($download->expires_at) < time()) {
            flash('msg_error', 'This download link has expired.');
            return $this->redirectOrError($request, 'This download link has expired.');
        }

        // Validate max downloads
        if ($download->max_downloads !== null && $download->download_count >= $download->max_downloads) {
            flash('msg_error', 'This download link has reached its maximum download limit.');
            return $this->redirectOrError($request, 'This download link has reached its maximum download limit.');
        }

        // Enforce authentication for all digital downloads
        $currentUser = $this->authService->currentUser();
        if (!$currentUser) {
            $this->authService->sessionStart();
            $_SESSION['redirect_after_login'] = $request->getServer('REQUEST_URI');
            flash('msg_error', 'Please log in to access this download.');
            return new RedirectResponse('/login');
        }

        // Verify that the user matches the account or identity associated with the original purchase
        $allowedUserId = $download->user_id ?: $download->order_user_id;
        $isOwner = false;

        if ($allowedUserId !== null && $allowedUserId > 0) {
            $isOwner = ((int)$currentUser->id === (int)$allowedUserId);
        } else {
            // Fallback to email verification for guest purchases
            $isOwner = ($currentUser->email === $download->customer_email);
        }

        if (!$isOwner) {
            $this->logger->warning("Unauthorized download attempt for token {token} by user {userId} ({email})", [
                'token' => $token,
                'userId' => $currentUser->id,
                'email' => $currentUser->email,
                'allowed_user_id' => $allowedUserId,
                'allowed_email' => $download->customer_email
            ]);
            flash('msg_error', 'You do not have permission to access this download.');
            return new RedirectResponse('/account');
        }

        // Retrieve product & variant to get file path
        $product = $this->productRepository->findById($download->product_id);
        if (!$product) {
            return new HtmlResponse($this->renderer->render('404', ['page_title' => 'Product Not Found']), 404);
        }

        $variant = $download->variant_id ? $this->productRepository->findVariantById($download->variant_id) : null;
        $filePath = $variant?->file_path ?: $product->file_path;

        if (empty($filePath)) {
            $this->logger->error("Download token {token} matches product {prodId} without a configured file path.", [
                'token' => $token,
                'prodId' => $product->id
            ]);
            flash('msg_error', 'This product does not have a file associated with it.');
            return $this->redirectOrError($request, 'This product does not have a file associated with it.');
        }

        // Resolve absolute or relative path
        $fullPath = $filePath;
        if (strpos($filePath, '/') !== 0 && strpos($filePath, ':\\') !== 1) { // not absolute path
            // Check in project root
            $possiblePaths = [
                __DIR__ . '/../../' . $filePath,
                __DIR__ . '/../../storage/downloads/' . $filePath,
                __DIR__ . '/../../storage/' . $filePath
            ];
            $fullPath = $possiblePaths[0];
            foreach ($possiblePaths as $p) {
                if (file_exists($p)) {
                    $fullPath = $p;
                    break;
                }
            }
        }

        if (!file_exists($fullPath)) {
            $this->logger->error("Digital download file not found on disk: " . $fullPath);
            flash('msg_error', 'The requested file is currently unavailable. Please contact support.');
            return $this->redirectOrError($request, 'The requested file is currently unavailable. Please contact support.');
        }

        // Increment download count
        $this->virtualProductService->incrementDownloadCount($token);

        $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
        $filename = basename($fullPath);

        return new FileResponse($fullPath, $filename, $mime);
    }

    private function redirectOrError(Request $request, string $message): Response {
        if ($this->authService->currentUser()) {
            return new RedirectResponse('/account/downloads');
        }
        return new RedirectResponse('/');
    }
}
