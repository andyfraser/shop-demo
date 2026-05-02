<?php
namespace App\Controllers;

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

    public function list() {
        $returns = $this->returnService->getAllForAdmin();

        $this->renderer->adminRender('returns_list', [
            'page_title' => 'Return Requests',
            'active'     => 'returns',
            'returns'    => $returns,
            'flash_msg'  => flash('msg'),
            'flash_error'=> flash('msg_error'),
        ]);
    }

    public function detail() {
        $id = (int)($_GET['id'] ?? 0);
        $return = $this->returnService->findById($id);

        if (!$return) {
            redirect('/admin/returns');
        }

        $order = $this->orderService->findById($return->order_id);

        $this->renderer->adminRender('returns_detail', [
            'page_title' => 'Return Request #' . $id,
            'active'     => 'returns',
            'return'     => $return,
            'order'      => $order,
        ]);
    }

    public function approve() {
        $id = (int)($_POST['id'] ?? 0);
        $refundDelivery = isset($_POST['refund_delivery']) && $_POST['refund_delivery'] === '1';
        $redirectTo = $_POST['redirect_to'] ?? '/admin/returns';
        $user = $this->auth->currentUser();

        if ($this->returnService->approveReturn($id, $refundDelivery, $user?->id)) {
            flash('msg', 'Return approved and refund processed.');
        } else {
            flash('msg_error', 'Failed to approve return.');
        }

        redirect($redirectTo);
    }

    public function reject() {
        $id = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['reject_reason'] ?? '');
        $redirectTo = $_POST['redirect_to'] ?? '/admin/returns';
        $user = $this->auth->currentUser();

        if ($this->returnService->rejectReturn($id, $reason, $user?->id)) {
            flash('msg', 'Return request rejected.');
        } else {
            flash('msg_error', 'Failed to reject return.');
        }

        redirect($redirectTo);
    }
}
