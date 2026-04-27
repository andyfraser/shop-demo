<?php // templates/order_confirm.php ?>
<div class="container" style="max-width:700px;">
  <div style="text-align:center;padding:3rem 0 2rem;">
    <div style="font-size:4rem;margin-bottom:1rem;">✅</div>
    <h1 style="font-family:var(--font-display);font-size:2rem;margin-bottom:.5rem;">
      <?= $order->status === 'pending' && isset($_SESSION['last_order_id']) && $_SESSION['last_order_id'] == $order->id ? 'Order Confirmed!' : 'Order Details' ?>
    </h1>
    <p style="color:var(--ink-2);">Order details and history for order <?= $order->getFormattedId() ?></p>
  </div>

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

    <div style="margin-top:1.2rem;">
      <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-2);margin-bottom:.3rem;">Customer</div>
      <div style="font-size:.875rem;"><strong><?= h($order->customer_name) ?></strong> (<?= h($order->customer_email) ?>)</div>
    </div>

    <div style="margin-top:1.2rem;">
      <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-2);margin-bottom:.3rem;">Shipping to</div>
      <div style="font-size:.875rem;"><?= nl2br(h($order->shipping_address)) ?></div>
    </div>
  </div>

  <div style="display:flex;gap:1rem;margin-top:1.5rem;justify-content:center;">
    <?php if ($current_user): ?>
      <a href="/account" class="btn btn-outline">View My Orders</a>
    <?php endif; ?>
    <a href="/" class="btn btn-primary">Continue Shopping</a>
  </div>
</div>
