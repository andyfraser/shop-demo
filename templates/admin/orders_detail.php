<?php // templates/admin/orders_detail.php 

$order_status = $order->status;
$available_actions = [];

$getStatusBadgeClass = function($status) {
    return match($status) {
        'pending'        => 'badge-warning',
        'confirmed'      => 'badge-info',
        'shipped'        => 'badge-info',
        'delivered'      => 'badge-success',
        'cancelled'      => 'badge-danger',
        'returning'      => 'badge-warning',
        'refunded'       => 'badge-success',
        'not refunded'   => 'badge-danger',
        'fully refunded' => 'badge-success',
        'partial refund' => 'badge-success',
        default          => 'badge-neutral',
    };
};

switch($order_status) {
    case 'pending':
        $available_actions = [
            ['status' => 'confirmed', 'label' => 'Confirm Order', 'class' => 'btn-primary'],
            ['status' => 'cancelled', 'label' => 'Cancel Order', 'class' => 'btn-outline', 'danger' => true]
        ];
        break;
    case 'confirmed':
        $available_actions = [
            ['status' => 'shipped', 'label' => 'Mark as Shipped', 'class' => 'btn-primary'],
            ['status' => 'cancelled', 'label' => 'Cancel Order', 'class' => 'btn-outline', 'danger' => true]
        ];
        break;
    case 'shipped':
        $available_actions = [
            ['status' => 'delivered', 'label' => 'Mark as Delivered', 'class' => 'btn-primary']
        ];
        break;
}

?>

<style>
.timeline {
  position: relative;
  padding-left: 1.5rem;
  margin-top: 1rem;
}
.timeline::before {
  content: '';
  position: absolute;
  left: 0.25rem;
  top: 0.5rem;
  bottom: 0.5rem;
  width: 2px;
  background: var(--line);
}
.timeline-item {
  position: relative;
  margin-bottom: 1.2rem;
}
.timeline-dot {
  position: absolute;
  left: -1.5rem;
  top: 0.35rem;
  width: 0.6rem;
  height: 0.6rem;
  border-radius: 50%;
  background: var(--accent-2);
  border: 2px solid var(--bg-card);
}
.timeline-content {
  font-size: 0.85rem;
}
.timeline-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.1rem;
}
.timeline-status {
  font-weight: 600;
  text-transform: capitalize;
}
.timeline-date {
  color: var(--ink-2);
  font-size: 0.75rem;
}
.timeline-notes {
  color: var(--ink-2);
  margin-top: 0.2rem;
  font-style: italic;
}
.timeline-user {
  font-size: 0.75rem;
  color: var(--ink-3);
  margin-top: 0.1rem;
}
</style>

<div class="admin-topbar">
  <h1>Order <?= $order->getFormattedId() ?></h1>
  <div class="actions">
    <a href="/admin/orders" class="btn btn-outline">← All Orders</a>
  </div>
</div>

<div class="content">
  <?php if ($flash_msg): ?>
    <div class="alert alert-success"><?= h($flash_msg) ?></div>
  <?php endif; ?>
  <?php if ($flash_error ?? false): ?>
    <div class="alert alert-danger"><?= h($flash_error) ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start;">
    <div>
      <div class="card" style="margin-bottom:1.5rem;">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:1.2rem;">
          <h2 style="font-family:var(--font-display);font-size:1.1rem;">Order Items</h2>
          <span class="badge <?= $order->getStatusBadgeClass() ?>" style="font-size:.8rem;">
            <?= ucfirst($order->status) ?>
          </span>
        </div>
        <table class="data-table" style="box-shadow:none;">
          <thead>
            <tr><th>Product</th><th>Unit Price</th><th>VAT Rate</th><th>Qty</th><th>Subtotal</th></tr>
          </thead>
          <tbody>
            <?php foreach ($order_items as $item): ?>
              <tr>
                <td>
                  <a href="/product/<?= h($item->slug) ?>"><?= h($item->product_name ?? $item->name) ?></a>
                  <?php if ($item->variant_name): ?>
                    <div style="font-size:0.8rem;color:var(--ink-2);margin-top:0.2rem;">
                      Option: <?= h($item->variant_name) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td><?= money($item->unit_price) ?></td>
                <td><?= (float)$item->vat_rate ?>%</td>
                <td><?= $item->quantity ?></td>
                <td>
                  <strong><?= money($item->getSubtotal()) ?></strong><br>
                  <small style="font-weight:400;color:var(--ink-2);">Incl. <?= money($item->vat_amount) ?> VAT</small>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="4" style="text-align:right;font-weight:600;padding:.8rem 1rem;">Subtotal</td>
              <td style="padding:.8rem 1rem;">
                <strong><?= money($order->total - ($order->delivery_cost ?? 0)) ?></strong>
              </td>
            </tr>
            <?php if ($order->delivery_method): ?>
            <tr>
              <td colspan="4" style="text-align:right;font-weight:600;padding:.8rem 1rem;">Delivery (<?= h($order->delivery_method) ?>)</td>
              <td style="padding:.8rem 1rem;">
                <strong><?= money($order->delivery_cost) ?></strong>
              </td>
            </tr>
            <?php endif; ?>
            <tr>
              <td colspan="4" style="text-align:right;font-weight:700;padding:.8rem 1rem;font-size:1.1rem;">Total</td>
              <td style="padding:.8rem 1rem;">
                <strong style="font-size:1.2rem;color:var(--accent-2);"><?= money($order->total) ?></strong><br>
                <div style="font-weight:400;font-size:.8rem;color:var(--ink-2);">Includes <?= money($order->total_vat_amount) ?> VAT</div>
              </td>
            </tr>
            <?php if ($order->refunded_amount > 0): ?>
            <tr style="color:var(--danger-ink);">
              <td colspan="4" style="text-align:right;font-weight:600;padding:.8rem 1rem;">Refunded</td>
              <td style="padding:.8rem 1rem;">
                <strong>-<?= money($order->refunded_amount) ?></strong>
                <?php if ($order->delivery_refunded): ?>
                  <div style="font-size:0.75rem;">(Incl. delivery)</div>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <td colspan="4" style="text-align:right;font-weight:700;padding:.8rem 1rem;font-size:1.1rem;">Final Total</td>
              <td style="padding:.8rem 1rem;">
                <strong style="font-size:1.2rem;"><?= money($order->total - $order->refunded_amount) ?></strong>
              </td>
            </tr>
            <?php endif; ?>
          </tfoot>
        </table>
      </div>

      <?php if (!empty($returns)): ?>
        <div class="card" style="margin-bottom:1.5rem;border-left:4px solid var(--accent-2);">
          <h2 style="font-family:var(--font-display);font-size:1.1rem;margin-bottom:1rem;">Returns</h2>
          <table class="data-table" style="box-shadow:none;">
            <thead>
              <tr><th>Return #</th><th>Date</th><th>Status</th><th>Refund</th><th>Reason</th></tr>
            </thead>
            <tbody>
              <?php foreach ($returns as $r): ?>
                <tr>
                  <td><a href="/admin/returns/detail?id=<?= $r->id ?>"><strong>#<?= str_pad($r->id, 4, '0', STR_PAD_LEFT) ?></strong></a></td>
                  <td style="font-size:.85rem;"><?= date('d M Y', strtotime($r->created_at)) ?></td>
                  <td><span class="badge <?= $r->getStatusBadgeClass() ?>"><?= ucfirst($r->status) ?></span></td>
                  <td><?= $r->refund_amount > 0 ? money($r->refund_amount) : '-' ?></td>
                  <td style="font-size:.85rem;color:var(--ink-2);max-width:200px;"><?= h($r->reason) ?></td>
                </tr>
                <?php if (!empty($r->items)): ?>
                  <tr style="background:var(--bg-body);"><td colspan="5" style="padding:0.5rem 1rem;">
                    <div style="font-size:0.75rem;text-transform:uppercase;color:var(--ink-3);margin-bottom:0.3rem;">Items in this return:</div>
                    <ul style="margin:0;padding-left:1.2rem;font-size:0.85rem;">
                      <?php foreach ($r->items as $ri): ?>
                        <li><?= $ri->quantity ?>x <?= h($ri->product_name) ?><?php if($ri->variant_name): ?> (<?= h($ri->variant_name) ?>)<?php endif; ?></li>
                      <?php endforeach; ?>
                    </ul>
                  </td></tr>
                <?php endif; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <?php if ($order->shipping_address): ?>
        <div class="card">
          <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.07em;color:var(--ink-2);margin-bottom:.75rem;">
            Shipping Address
          </h3>
          <div style="font-size:.9rem;line-height:1.8;"><?= nl2br(h($order->shipping_address)) ?></div>
          <?php if ($order->notes): ?>
            <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--line);">
              <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.07em;color:var(--ink-2);margin-bottom:.4rem;">Notes from Customer</div>
              <div style="font-size:.875rem;"><?= nl2br(h($order->notes)) ?></div>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <div>
      <div class="card" style="margin-bottom:1rem;">
        <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.07em;color:var(--ink-2);margin-bottom:.75rem;">
          Customer
        </h3>
        <div style="font-weight:600;margin-bottom:.2rem;"><?= h($order->user_name ?? 'Guest') ?></div>
        <div style="font-size:.85rem;color:var(--ink-2);"><?= h($order->user_email ?? '') ?></div>
        <div style="font-size:.8rem;color:var(--ink-2);margin-top:.75rem;">
          Order placed <?= date('d M Y, H:i', strtotime($order->created_at)) ?>
        </div>
      </div>

      <div class="card" style="margin-bottom:1rem;">
        <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.07em;color:var(--ink-2);margin-bottom:.75rem;">
          Quick Actions
        </h3>
        
        <?php if (empty($available_actions)): ?>
            <p style="font-size:.85rem;color:var(--ink-3);text-align:center;padding:1rem 0;">No actions available for status: <strong><?= ucfirst($order->status) ?></strong></p>
        <?php else: ?>
            <?php foreach ($available_actions as $action): ?>
                <form method="POST" action="/admin/orders/update-status" style="margin-bottom:.5rem;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$order->id ?>">
                    <input type="hidden" name="status" value="<?= $action['status'] ?>">
                    <button type="submit" class="btn <?= $action['class'] ?> btn-sm" 
                            style="width:100%;justify-content:center;<?= isset($action['danger']) ? 'color:var(--danger-ink);border-color:var(--danger-ink);' : '' ?>"
                            onclick="return <?= isset($action['danger']) ? "confirm('Are you sure?')" : 'true' ?>">
                        <?= $action['label'] ?>
                    </button>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>

        <hr style="margin:1rem 0;border:none;border-top:1px solid var(--line);">

        <h4 style="font-size:.75rem;text-transform:uppercase;color:var(--ink-2);margin-bottom:.5rem;">Manual Override</h4>
        <form method="POST" action="/admin/orders/update-status">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$order->id ?>">
          <div class="form-group" style="margin-bottom:.5rem;">
            <select name="status" class="form-control" style="font-size:.85rem;min-height:32px;padding:.3rem .6rem;">
              <?php foreach (['pending','confirmed','shipped','delivered','cancelled','returning','refunded','not refunded','fully refunded', 'partial refund'] as $s): ?>
                <option value="<?= $s ?>" <?= $order->status === $s ? 'selected' : '' ?>>
                  <?= ucfirst($s) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:.5rem;">
            <textarea name="notes" class="form-control" placeholder="Optional notes..." style="font-size:.8rem;height:60px;padding:.4rem .6rem;"></textarea>
          </div>
          <button type="submit" class="btn btn-outline btn-sm" style="width:100%;justify-content:center;">
            Update Status
          </button>
        </form>
      </div>

      <div class="card">
        <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.07em;color:var(--ink-2);margin-bottom:.75rem;">
          Order History
        </h3>
        <div class="timeline">
          <?php foreach ($history as $h): ?>
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <div class="timeline-content">
                <div class="timeline-header">
                  <span class="timeline-status badge <?= $getStatusBadgeClass($h['status']) ?>" style="font-size:.7rem;">
                    <?= h(ucfirst($h['status'])) ?>
                  </span>
                  <span class="timeline-date"><?= date('d M, H:i', strtotime($h['created_at'])) ?></span>
                </div>
                <?php if ($h['notes']): ?>
                  <div class="timeline-notes"><?= h($h['notes']) ?></div>
                <?php endif; ?>
                <?php if ($h['user_name']): ?>
                  <div class="timeline-user">by <?= h($h['user_name']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
