<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Renderer;
use App\Services\SecurityServiceInterface;
use App\Services\ReturnServiceInterface;

class AdminReturnsController {
    public function __construct(
        private ReturnServiceInterface $returnService,
        private \App\Services\OrderServiceInterface $orderService,
        private \App\Services\AuthServiceInterface $auth,
        private Renderer $renderer,
        private SecurityServiceInterface $security,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function list(Request $request): Response {
        $returns = $this->returnService->getAllForAdmin();

        return new HtmlResponse($this->renderer->adminRender('returns_list', [
            'page_title' => 'Return Requests',
            'active'     => 'returns',
            'returns'    => $returns,
            'flash_msg'  => flash('msg'),
            'flash_error'=> flash('msg_error'),
        ]));
    }

    public function detail(Request $request): Response {
        $id = (int)$request->getQuery('id', 0);
        $return = $this->returnService->findById($id);

        if (!$return) {
            return new RedirectResponse('/admin/returns');
        }

        $order = $this->orderService->findById($return->order_id);

        return new HtmlResponse($this->renderer->adminRender('returns_detail', [
            'page_title' => 'Return Request #' . $id,
            'active'     => 'returns',
            'return'     => $return,
            'order'      => $order,
        ]));
    }

    public function approve(Request $request): Response {
        $id = (int)$request->getPost('id', 0);
        $refundDelivery = $request->getPost('refund_delivery') === '1';
        $redirectTo = $request->getPost('redirect_to', '/admin/returns');
        $user = $this->auth->currentUser();

        if ($this->returnService->approveReturn($id, $refundDelivery, $user?->id)) {
            flash('msg', 'Return approved and refund processed.');
        } else {
            flash('msg_error', 'Failed to approve return.');
        }

        return new RedirectResponse($redirectTo);
    }

    public function reject(Request $request): Response {
        $id = (int)$request->getPost('id', 0);
        $reason = trim($request->getPost('reject_reason', ''));
        $redirectTo = $request->getPost('redirect_to', '/admin/returns');
        $user = $this->auth->currentUser();

        if ($this->returnService->rejectReturn($id, $reason, $user?->id)) {
            flash('msg', 'Return request rejected.');
        } else {
            flash('msg_error', 'Failed to reject return.');
        }

        return new RedirectResponse($redirectTo);
    }
}
