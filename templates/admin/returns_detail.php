<?php // templates/admin/returns_detail.php ?>

<div class="admin-topbar">
  <h1>Return Request #<?= $return->id ?></h1>
  <div class="actions">
    <a href="/admin/returns" class="btn btn-outline btn-sm">Back to List</a>
  </div>
</div>

<div class="content">
  <div style="display:grid;grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start;">
    <div class="card">
      <h3 style="font-family:var(--font-display);margin-bottom:1rem;">Returned Items</h3>
      <table class="data-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Quantity</th>
            <th style="text-align:right;">Price</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($return->items as $item): ?>
            <tr>
              <td>
                <?= h($item->product_name) ?>
                <?php if ($item->variant_name): ?><br><small style="color:var(--ink-2);">Option: <?= h($item->variant_name) ?></small><?php endif; ?>
              </td>
              <td><?= $item->quantity ?></td>
              <td style="text-align:right;"><?= money($item->unit_price) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div style="margin-top:2rem;">
        <h3 style="font-family:var(--font-display);margin-bottom:.5rem;">Reason for Return</h3>
        <div style="padding:1rem;background:var(--sand);border-radius:var(--radius);border:1px solid var(--line);font-size:.875rem;">
          <?= nl2br(h($return->reason)) ?>
        </div>
      </div>
    </div>

    <div class="card">
      <h3 style="font-family:var(--font-display);margin-bottom:1rem;">Manage Request</h3>
      
      <div style="margin-bottom:1.5rem;">
        <div style="font-size:.75rem;text-transform:uppercase;color:var(--ink-2);margin-bottom:.25rem;">Current Status</div>
        <?= new \App\View\Components\StatusBadge(ucfirst($return->status), $return->getStatusBadgeClass()) ?>
      </div>

      <?php if ($return->status === 'pending'): ?>
        <div style="display:flex;flex-direction:column;gap:1rem;">
          <form action="/admin/returns/approve" method="POST" onsubmit="return confirm('Are you sure you want to approve this return and process a refund?');">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= $return->id ?>">
            
            <?php if ($order && $order->delivery_cost > 0 && !$order->delivery_refunded): ?>
              <div style="margin-bottom: 1rem; padding: .75rem; background: var(--sand); border-radius: var(--radius); border: 1px solid var(--line);">
                <label style="display: flex; align-items: center; gap: .5rem; cursor: pointer; font-size: .875rem;">
                  <input type="checkbox" name="refund_delivery" value="1" style="width: 1.2rem; height: 1.2rem;">
                  <span>Refund delivery cost (<?= money($order->delivery_cost) ?>)</span>
                </label>
                <div style="font-size: .75rem; color: var(--ink-2); margin-top: .25rem; margin-left: 1.7rem;">
                  Include original shipping charge in the refund.
                </div>
              </div>
            <?php elseif ($order && $order->delivery_refunded): ?>
              <div style="margin-bottom: 1rem; font-size: .875rem; color: var(--ink-2);">
                ✅ Delivery cost already refunded.
              </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary" style="width:100%;">Approve & Refund</button>
          </form>

          <div style="border-top:1px solid var(--line);margin-top:.5rem;padding-top:1.5rem;">
            <form action="/admin/returns/reject" method="POST">
              <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= $return->id ?>">
              <div class="form-group">
                <label>Rejection Reason</label>
                <textarea name="reject_reason" class="form-control" style="height:80px;" required placeholder="Why is this return being rejected?"></textarea>
              </div>
              <button type="submit" class="btn btn-outline" style="width:100%;color:#c0392b;border-color:#c0392b;">Reject Request</button>
            </form>
          </div>
        </div>
      <?php elseif ($return->status === 'approved'): ?>
        <div class="alert alert-success">
          Approved on <?= date('d M Y', strtotime($return->updated_at)) ?>.<br>
          Refund: <strong><?= money($return->refund_amount) ?></strong>
        </div>
      <?php elseif ($return->status === 'rejected'): ?>
        <div class="alert alert-error">
          Rejected on <?= date('d M Y', strtotime($return->updated_at)) ?>.
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
