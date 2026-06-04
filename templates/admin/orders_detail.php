<?php // templates/admin/orders_detail.php 

$order_status = $order->status;
$available_actions = [];

$getStatusBadgeClass = function($status) {
    return match($status) {
        'pending'        => 'badge-warning',
        'paid'           => 'badge-success',
        'confirmed'      => 'badge-info',
        'shipped'        => 'badge-info',
        'delivered'      => 'badge-success',
        'cancelled'      => 'badge-danger',
        'returning'      => 'badge-warning',
        'not refunded'   => 'badge-danger',
        'fully refunded' => 'badge-success',
        'partial refund' => 'badge-success',
        default          => 'badge-neutral',
    };
};

switch($order_status) {
    case 'pending':
        $available_actions = [
            ['status' => 'paid', 'label' => 'Mark as Paid', 'class' => 'btn-primary'],
            ['status' => 'confirmed', 'label' => 'Confirm Order', 'class' => 'btn-outline'],
            ['status' => 'cancelled', 'label' => 'Cancel Order', 'class' => 'btn-outline', 'danger' => true]
        ];
        break;
    case 'paid':
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

  <div class="grid-sidebar" style="grid-template-columns:1fr 320px;">
    <div>
      <div class="card mb-3">
        <div class="flex-between flex-start mb-2">
          <h2 class="section-title">Order Items</h2>
          <span class="badge <?= $order->getStatusBadgeClass() ?>" style="font-size:.8rem;">
            <?= ucfirst($order->status) ?>
          </span>
        </div>
        <table class="data-table no-shadow">
          <thead>
            <tr><th>Product</th><th>Unit Price</th><th>VAT Rate</th><th>Qty</th><th>Subtotal</th></tr>
          </thead>
          <tbody>
            <?php foreach ($order_items as $item): ?>
              <tr>
                <td>
                  <a href="/product/<?= h($item->slug) ?>"><?= h($item->product_name ?? $item->name) ?></a>
                  <?php if ($item->variant_name): ?>
                    <div class="text-xs text-muted mt-1">
                      Option: <?= h($item->variant_name) ?>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($item->bundle_components)): ?>
                    <div class="text-xs mt-1" style="background:var(--bg-2);padding:0.25rem 0.4rem;border-radius:4px;display:block;border:1px solid var(--line);">
                      <div class="text-muted fw-600 mb-1" style="text-transform:uppercase;font-size:0.6rem;letter-spacing:0.02em;">Includes:</div>
                      <ul style="margin:0;padding:0;list-style:none;">
                        <?php foreach ($item->bundle_components as $bc): ?>
                          <li><?= $bc['qty'] ?> × <?= h($bc['name']) ?></li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                  <?php endif; ?>
                  <?php if ($item->metadata): 
                    $meta = json_decode($item->metadata, true);
                    if (!empty($meta['recipient_email'])):
                  ?>
                    <div class="text-xs mt-1" style="background:var(--bg-2);padding:0.25rem 0.4rem;border-radius:4px;display:inline-block;border:1px solid var(--line);">
                      <strong>Recipient:</strong> <?= h($meta['recipient_email']) ?>
                      <?php if (!empty($meta['sender_name'])): ?><br><strong>Sender:</strong> <?= h($meta['sender_name']) ?><?php endif; ?>
                      <?php if (!empty($meta['message'])): ?><br><strong>Message:</strong> <em><?= h($meta['message']) ?></em><?php endif; ?>
                    </div>
                  <?php 
                    endif;
                  endif; ?>
                </td>
                <td><?= money($item->unit_price) ?></td>
                <td><?= (float)$item->vat_rate ?>%</td>
                <td><?= $item->quantity ?></td>
                <td>
                  <strong><?= money($item->getSubtotal()) ?></strong><br>
                  <small class="text-muted" style="font-weight:400;">Incl. <?= money($item->vat_amount) ?> VAT</small>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="4" class="text-right font-bold" style="padding:.8rem 1rem;">Subtotal</td>
              <td style="padding:.8rem 1rem;">
                <strong><?= money($order->total - ($order->delivery_cost ?? 0) + $order->discount_amount + $order->gift_card_amount) ?></strong>
              </td>
            </tr>
            <?php if ($order->discount_amount > 0): ?>
            <tr>
              <td colspan="4" class="text-right font-bold" style="padding:.8rem 1rem;color:var(--accent);">
                Discount Total
              </td>
              <td style="padding:.8rem 1rem;color:var(--accent);">
                <strong>-<?= money($order->discount_amount) ?></strong>
              </td>
            </tr>
            <?php if (!empty($order->applied_promotions)): ?>
              <?php foreach ($order->applied_promotions as $promo): ?>
                <tr class="bg-sand text-xs" style="color:var(--ink-2);">
                  <td colspan="4" class="text-right" style="padding:.4rem 1rem;">
                    <?= h($promo['promotion_name']) ?>
                    <?php if (!empty($promo['promo_code'])): ?>
                      <span class="text-muted">(<?= h($promo['promo_code']) ?>)</span>
                    <?php endif; ?>
                  </td>
                  <td style="padding:.4rem 1rem;">
                    -<?= money($promo['discount_amount']) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php elseif ($order->applied_promo_name): ?>
              <tr class="bg-sand text-xs" style="color:var(--ink-2);">
                <td colspan="4" class="text-right" style="padding:.4rem 1rem;">
                  Via: <?= h($order->applied_promo_name) ?> 
                  <?= $order->applied_promo_code ? '(' . h($order->applied_promo_code) . ')' : '' ?>
                </td>
                <td style="padding:.4rem 1rem;">-<?= money($order->discount_amount) ?></td>
              </tr>
            <?php endif; ?>
            <?php endif; ?>
            <?php if ($order->delivery_method): ?>
            <tr>
              <td colspan="4" class="text-right font-bold" style="padding:.8rem 1rem;">Delivery (<?= h($order->delivery_method) ?>)</td>
              <td style="padding:.8rem 1rem;">
                <strong><?= money($order->delivery_cost) ?></strong>
              </td>
            </tr>
            <?php endif; ?>
            <?php if ($order->gift_card_amount > 0): ?>
            <tr>
              <td colspan="4" class="text-right font-bold" style="padding:.8rem 1rem;color:var(--accent);">
                Paid using gift cards
              </td>
              <td style="padding:.8rem 1rem;color:var(--accent);">
                <strong>-<?= money($order->gift_card_amount) ?></strong>
              </td>
            </tr>
            <?php endif; ?>
            <tr>
              <td colspan="4" class="text-right font-bold text-lg" style="padding:.8rem 1rem;">Total</td>
              <td style="padding:.8rem 1rem;">
                <strong class="text-lg" style="color:var(--accent-2);"><?= money($order->total) ?></strong><br>
                <div class="text-xs text-muted" style="font-weight:400;">Includes <?= money($order->total_vat_amount) ?> VAT</div>
              </td>
            </tr>
            <?php if ($order->refunded_amount > 0): ?>
            <tr style="color:var(--danger-ink);">
              <td colspan="4" class="text-right font-bold" style="padding:.8rem 1rem;">Refunded</td>
              <td style="padding:.8rem 1rem;">
                <strong>-<?= money($order->refunded_amount) ?></strong>
                <?php if ($order->delivery_refunded): ?>
                  <div class="text-xs">(Incl. delivery)</div>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <td colspan="4" class="text-right font-bold text-lg" style="padding:.8rem 1rem;">Final Total</td>
              <td style="padding:.8rem 1rem;">
                <strong class="text-lg"><?= money($order->total - $order->refunded_amount) ?></strong>
              </td>
            </tr>
            <?php endif; ?>
          </tfoot>
        </table>
      </div>

      <?php if (!empty($returns)): ?>
        <div class="card mb-3 border-left-accent">
          <h2 class="section-title">Returns</h2>
          <table class="data-table no-shadow">
            <thead>
              <tr><th>Return #</th><th>Date</th><th>Status</th><th>Refund</th><th>Reason</th></tr>
            </thead>
            <tbody>
              <?php foreach ($returns as $r): ?>
                <tr>
                  <td><a href="/admin/returns/detail?id=<?= $r->id ?>"><strong>#<?= str_pad($r->id, 4, '0', STR_PAD_LEFT) ?></strong></a></td>
                  <td class="text-sm"><?= date('d M Y', strtotime($r->created_at)) ?></td>
                  <td><span class="badge <?= $r->getStatusBadgeClass() ?>"><?= ucfirst($r->status) ?></span></td>
                  <td><?= $r->refund_amount > 0 ? money($r->refund_amount) : '-' ?></td>
                  <td class="text-sm text-muted nowrap" style="max-width:200px;"><?= h($r->reason) ?></td>
                </tr>
                <?php if (!empty($r->items)): ?>
                  <tr class="bg-sand"><td colspan="5" style="padding:0.5rem 1rem;">
                    <div class="text-xs text-muted font-bold mb-1" style="text-transform:uppercase;">Items in this return:</div>
                    <ul class="text-sm" style="margin:0;padding-left:1.2rem;">
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
          <h3 class="label-upper">
            Shipping Address
          </h3>
          <div class="text-sm" style="line-height:1.8;"><?= nl2br(h($order->shipping_address)) ?></div>
          <?php if ($order->notes): ?>
            <div class="mt-2 border-top">
              <div class="label-upper mb-1">Notes from Customer</div>
              <div class="text-sm"><?= nl2br(h($order->notes)) ?></div>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <div>
      <div class="card mb-2">
        <h3 class="label-upper">
          Customer
        </h3>
        <div class="font-bold mb-1"><?= h($order->user_name ?? 'Guest') ?></div>
        <div class="text-sm text-muted"><?= h($order->user_email ?? '') ?></div>
        <div class="text-xs text-muted mt-2">
          Order placed <?= date('d M Y, H:i', strtotime($order->created_at)) ?>
        </div>
      </div>

      <div class="card mb-2">
        <h3 class="label-upper">
          Quick Actions
        </h3>
        
        <?php if (empty($available_actions)): ?>
            <p class="text-sm text-muted text-center" style="padding:1rem 0;">No actions available for status: <strong><?= ucfirst($order->status) ?></strong></p>
        <?php else: ?>
            <?php foreach ($available_actions as $action): ?>
                <form method="POST" action="/admin/orders/update-status" class="mb-1">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$order->id ?>">
                    <input type="hidden" name="status" value="<?= $action['status'] ?>">
                    <button type="submit" class="btn <?= $action['class'] ?> btn-sm w-100 flex-center" 
                            style="justify-content:center;<?= isset($action['danger']) ? 'color:var(--danger-ink);border-color:var(--danger-ink);' : '' ?>"
                            onclick="return <?= isset($action['danger']) ? "confirm('Are you sure?')" : 'true' ?>">
                        <?= $action['label'] ?>
                    </button>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>

        <hr class="mb-2 border-top" style="border:none;margin-top:1rem;">

        <h4 class="text-xs text-muted mb-1" style="text-transform:uppercase;">Manual Override</h4>
        <form method="POST" action="/admin/orders/update-status">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$order->id ?>">
          <div class="form-group mb-1">
            <select name="status" class="form-control text-sm" style="min-height:32px;padding:.3rem .6rem;">
              <?php foreach (['pending','paid','confirmed','shipped','delivered','cancelled','returning','not refunded','fully refunded', 'partial refund'] as $s): ?>
                <option value="<?= $s ?>" <?= $order->status === $s ? 'selected' : '' ?>>
                  <?= ucfirst($s) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group mb-1">
            <textarea name="notes" class="form-control text-sm" placeholder="Optional notes..." style="height:60px;padding:.4rem .6rem;"></textarea>
          </div>
          <button type="submit" class="btn btn-outline btn-sm w-100 flex-center" style="justify-content:center;">
            Update Status
          </button>
        </form>
      </div>

      <div class="card">
        <h3 class="label-upper">
          Order History
        </h3>
        <div class="timeline">
          <?php foreach ($history as $h): ?>
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <div class="timeline-content">
                <div class="timeline-header">
                  <span class="timeline-status badge <?= $h->getStatusBadgeClass() ?>" style="font-size:.7rem;">
                    <?= h(ucfirst($h->status)) ?>
                  </span>
                  <span class="timeline-date"><?= date('d M, H:i', strtotime($h->created_at)) ?></span>
                </div>
                <?php if ($h->notes): ?>
                  <div class="timeline-notes"><?= h($h->notes) ?></div>
                <?php endif; ?>
                <?php if ($h->user_name): ?>
                  <div class="timeline-user">by <?= h($h->user_name) ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
