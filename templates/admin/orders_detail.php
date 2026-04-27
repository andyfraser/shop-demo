<?php // templates/admin/orders_detail.php ?>

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

  <div style="display:grid;grid-template-columns:1fr 300px;gap:1.5rem;align-items:start;">
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
          </tfoot>
        </table>
      </div>

      <?php if ($order->shipping_address): ?>
        <div class="card">
          <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.07em;color:var(--ink-2);margin-bottom:.75rem;">
            Shipping Address
          </h3>
          <div style="font-size:.9rem;line-height:1.8;"><?= nl2br(h($order->shipping_address)) ?></div>
          <?php if ($order->notes): ?>
            <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--line);">
              <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.07em;color:var(--ink-2);margin-bottom:.4rem;">Notes</div>
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

      <div class="card">
        <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.07em;color:var(--ink-2);margin-bottom:.75rem;">
          Update Status
        </h3>
        <form method="POST" action="/admin/orders/status">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$order->id ?>">
          <div class="form-group">
            <select name="status" class="form-control">
              <?php foreach (['pending','confirmed','shipped','delivered','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $order->status === $s ? 'selected' : '' ?>>
                  <?= ucfirst($s) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary btn-sm" style="width:100%;justify-content:center;">
            Update Status
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
