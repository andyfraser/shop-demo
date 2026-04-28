<?php // templates/order_confirm.php ?>
<div class="container" style="max-width:700px;">
  <div style="text-align:center;padding:3rem 0 2rem;">
    <div style="font-size:4rem;margin-bottom:1rem;">✅</div>
    <h1 style="font-family:var(--font-display);font-size:2rem;margin-bottom:.5rem;">
      <?= $order->status === 'pending' && isset($_SESSION['last_order_id']) && $_SESSION['last_order_id'] == $order->id ? 'Order Confirmed!' : 'Order Details' ?>
    </h1>
    <p style="color:var(--ink-2);">Order details and history for order <?= $order->getFormattedId() ?></p>
  </div>

  <?php if ($flash_msg ?? false): ?>
    <div class="alert alert-success" style="margin-bottom:1.5rem;"><?= h($flash_msg) ?></div>
  <?php endif; ?>
  <?php if ($flash_error ?? false): ?>
    <div class="alert alert-danger" style="margin-bottom:1.5rem;"><?= h($flash_error) ?></div>
  <?php endif; ?>

  <div class="card">
    <div style="display:flex;justify-content:space-between;margin-bottom:1.2rem;flex-wrap:wrap;gap:.5rem;">
      <div>
        <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-2);">Order #</div>
        <strong><?= str_pad($order->id, 6, '0', STR_PAD_LEFT) ?></strong>
      </div>
      <div>
        <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-2);">Date</div>
        <strong><?= date('d M Y', strtotime($order->created_at)) ?></strong>
      </div>
      <div>
        <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-2);">Status</div>
        <span class="badge <?= $order->getStatusBadgeClass() ?>"><?= h(ucfirst($order->status)) ?></span>
      </div>
      <div>
        <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-2);">Total</div>
        <strong style="color:var(--accent-2)"><?= money($order->total) ?></strong>
      </div>
    </div>

    <h3 style="font-family:var(--font-display);margin-bottom:.75rem;">Items</h3>
    <?php foreach ($order_items as $i => $item): ?>
      <div style="display:flex;justify-content:space-between;padding:.5rem 0;<?= $i < count($order_items) - 1 ? 'border-bottom:1px solid var(--line);' : '' ?>font-size:.875rem;">
        <span>
          <a href="/product/<?= h($item->slug) ?>"><?= h($item->name ?? $item->product_name) ?></a>
          <?php if ($item->variant_name): ?>
            <div style="font-size:.75rem;color:var(--ink-2);margin-top:.1rem;">Option: <?= h($item->variant_name) ?></div>
          <?php endif; ?>
          × <?= $item->quantity ?>
        </span>
        <strong><?= money($item->getSubtotal()) ?></strong>
      </div>
    <?php endforeach; ?>

    <div style="margin-top:1.2rem;border-top:2px solid var(--line);padding-top:1rem;">
      <div style="display:flex;justify-content:space-between;padding:.4rem 0;font-size:.875rem;">
        <span>Subtotal</span>
        <strong><?= money($order->total - ($order->delivery_cost ?? 0)) ?></strong>
      </div>
      <?php if ($order->delivery_method): ?>
      <div style="display:flex;justify-content:space-between;padding:.4rem 0;font-size:.875rem;">
        <span>Delivery (<?= h($order->delivery_method) ?>)</span>
        <strong><?= money($order->delivery_cost) ?></strong>
      </div>
      <?php endif; ?>
      <div style="display:flex;justify-content:space-between;padding:.75rem 0;font-size:1.15rem;font-weight:700;margin-top:.5rem;">
        <span>Total</span>
        <span style="color:var(--accent-2)"><?= money($order->total) ?></span>
      </div>
      <div style="font-size:.85rem;color:var(--ink-2);text-align:right;">
        Includes <?= money($order->total_vat_amount ?? 0) ?> VAT
      </div>
    </div>

    <?php if ($order->canBeCancelled()): ?>
      <div style="margin-top:1.5rem;padding-top:1.2rem;border-top:1px solid var(--line);display:flex;justify-content:flex-end;">
        <form action="/account/cancel-order" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= $order->id ?>">
          <button type="submit" class="btn btn-outline" style="color:var(--danger-ink);border-color:var(--danger-ink);">Cancel Order</button>
        </form>
      </div>
    <?php endif; ?>

    <div style="margin-top:1.2rem;">
      <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-2);margin-bottom:.3rem;">Customer</div>
      <div style="font-size:.875rem;"><strong><?= h($order->customer_name) ?></strong> (<?= h($order->customer_email) ?>)</div>
    </div>

    <div style="margin-top:1.2rem;">
      <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-2);margin-bottom:.3rem;">Shipping to</div>
      <div style="font-size:.875rem;"><?= nl2br(h($order->shipping_address)) ?></div>
    </div>

    <?php if ($order->canBeReturned() || !empty($returns)): ?>
      <?php 
        $returned_item_ids = [];
        foreach ($returns ?? [] as $ret) {
            foreach ($ret->items as $ri) {
                // Store the most recent status for this item
                $returned_item_ids[$ri->order_item_id] = $ret->status;
            }
        }
        
        $available_items = array_filter($order_items, function($item) use ($returned_item_ids) {
            return !isset($returned_item_ids[$item->id]);
        });
      ?>
      
      <div style="margin-top:2rem;border-top:2px dashed var(--line);padding-top:1.5rem;">
        <h3 style="font-family:var(--font-display);margin-bottom:.75rem;">Return Management</h3>
        
        <?php if ($order->canBeReturned() && count($available_items) > 0): ?>
          <p style="font-size:.875rem;color:var(--ink-2);margin-bottom:1rem;">Select the items you wish to return and provide a reason.</p>
          
          <form action="/account/request-return" method="POST">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="order_id" value="<?= $order->id ?>">
            
            <?php foreach ($order_items as $item): ?>
              <?php $is_returned = isset($returned_item_ids[$item->id]); ?>
              <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;font-size:.875rem; <?= $is_returned ? 'opacity:0.6;' : '' ?>">
                <?php if ($is_returned): ?>
                  <span style="color:var(--accent-2);font-weight:bold;">[<?= h(ucfirst($returned_item_ids[$item->id])) ?>]</span>
                <?php else: ?>
                  <input type="checkbox" name="items[<?= $item->id ?>]" value="<?= $item->quantity ?>" id="item_<?= $item->id ?>">
                <?php endif; ?>
                <label for="item_<?= $item->id ?>" style="<?= $is_returned ? 'text-decoration:line-through;' : '' ?>">
                  <?= h($item->product_name) ?>
                  <?php if ($item->variant_name): ?> (<?= h($item->variant_name) ?>)<?php endif; ?>
                  — <?= $item->quantity ?> unit(s)
                </label>
              </div>
            <?php endforeach; ?>
            
            <div style="margin-top:1rem;">
              <label style="display:block;font-size:.75rem;text-transform:uppercase;color:var(--ink-2);margin-bottom:.3rem;">Reason for Return</label>
              <textarea name="reason" class="form-control" style="width:100%;height:80px;" required placeholder="e.g., Wrong size, Damaged, Changed my mind..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top:1rem;width:100%;">Submit Return Request</button>
          </form>
        <?php else: ?>
          <p style="font-size:.875rem;color:var(--ink-2);">All items in this order have been returned or are in the process of being returned.</p>
          
          <div style="margin-top:1rem;">
            <?php foreach ($order_items as $item): ?>
              <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;font-size:.875rem; opacity:0.6;">
                <span style="color:var(--accent-2);font-weight:bold;">[<?= h(ucfirst($returned_item_ids[$item->id])) ?>]</span>
                <span style="text-decoration:line-through;">
                  <?= h($item->product_name) ?>
                  <?php if ($item->variant_name): ?> (<?= h($item->variant_name) ?>)<?php endif; ?>
                  — <?= $item->quantity ?> unit(s)
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($order->refund_status): ?>
      <div style="margin-top:1.5rem;padding:1rem;background:var(--bg-highlight);border-radius:var(--radius);border:1px solid var(--line);">
        <strong style="color:var(--accent-2);display:block;margin-bottom:.25rem;">Refund Processed</strong>
        <div style="font-size:.875rem;">
          Status: <span class="badge <?= $order->getStatusBadgeClass() ?>"><?= h(ucwords(str_replace('_', ' ', $order->status))) ?></span><br>
          Amount: <strong><?= money($order->refunded_amount) ?></strong>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div style="display:flex;gap:1rem;margin-top:1.5rem;justify-content:center;">
    <?php if ($current_user): ?>
      <a href="/account" class="btn btn-outline">View My Orders</a>
    <?php endif; ?>
    <a href="/" class="btn btn-primary">Continue Shopping</a>
  </div>
</div>
