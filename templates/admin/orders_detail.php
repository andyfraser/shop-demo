<?php // templates/admin/orders_detail.php
$badges = ['pending'=>'badge-warning','confirmed'=>'badge-info','shipped'=>'badge-info','delivered'=>'badge-success','cancelled'=>'badge-danger'];
?>

<div class="admin-topbar">
  <h1>Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h1>
  <div class="actions">
    <a href="orders.php" class="btn btn-outline">← All Orders</a>
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
          <span class="badge <?= $badges[$order['status']] ?>" style="font-size:.8rem;">
            <?= ucfirst($order['status']) ?>
          </span>
        </div>
        <table class="data-table" style="box-shadow:none;">
          <thead>
            <tr><th>Product</th><th>Unit Price</th><th>Qty</th><th>Subtotal</th></tr>
          </thead>
          <tbody>
            <?php foreach ($order_items as $item): ?>
              <tr>
                <td><a href="../product.php?slug=<?= h($item['slug']) ?>"><?= h($item['product_name']) ?></a></td>
                <td>£<?= number_format($item['unit_price'], 2) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><strong>£<?= number_format($item['unit_price'] * $item['quantity'], 2) ?></strong></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3" style="text-align:right;font-weight:600;padding:.8rem 1rem;">Total</td>
              <td style="padding:.8rem 1rem;">
                <strong style="font-size:1.05rem;">£<?= number_format($order['total'], 2) ?></strong>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>

      <?php if ($order['shipping_address']): ?>
        <div class="card">
          <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.07em;color:var(--ink-2);margin-bottom:.75rem;">
            Shipping Address
          </h3>
          <div style="font-size:.9rem;line-height:1.8;"><?= nl2br(h($order['shipping_address'])) ?></div>
          <?php if ($order['notes']): ?>
            <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--line);">
              <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.07em;color:var(--ink-2);margin-bottom:.4rem;">Notes</div>
              <div style="font-size:.875rem;"><?= nl2br(h($order['notes'])) ?></div>
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
        <div style="font-weight:600;margin-bottom:.2rem;"><?= h($order['user_name'] ?? 'Guest') ?></div>
        <div style="font-size:.85rem;color:var(--ink-2);"><?= h($order['user_email'] ?? '') ?></div>
        <div style="font-size:.8rem;color:var(--ink-2);margin-top:.75rem;">
          Order placed <?= date('d M Y, H:i', strtotime($order['created_at'])) ?>
        </div>
      </div>

      <div class="card">
        <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.07em;color:var(--ink-2);margin-bottom:.75rem;">
          Update Status
        </h3>
        <form method="POST">
          <div class="form-group">
            <select name="status" class="form-control">
              <?php foreach (['pending','confirmed','shipped','delivered','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
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
