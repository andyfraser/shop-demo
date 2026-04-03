<?php // templates/account.php
$status_badges = [
    'pending'   => 'badge-warning',
    'confirmed' => 'badge-info',
    'shipped'   => 'badge-info',
    'delivered' => 'badge-success',
    'cancelled' => 'badge-danger',
];
?>
<div class="container">
  <h1 class="page-title">My Account</h1>

  <div style="display:grid;grid-template-columns:280px 1fr;gap:2rem;align-items:start;">
    <div class="card">
      <div style="font-family:var(--font-display);font-size:1.2rem;font-weight:600;margin-bottom:.25rem;">
        <?= h($current_user['name']) ?>
      </div>
      <div style="color:var(--ink-2);font-size:.85rem;margin-bottom:1rem;"><?= h($current_user['email']) ?></div>
      <span class="badge <?= $current_user['role'] === 'admin' ? 'badge-danger' : 'badge-neutral' ?>"
            style="margin-bottom:1.2rem;display:inline-block;">
        <?= ucfirst($current_user['role']) ?>
      </span>
      <div style="font-size:.8rem;color:var(--ink-2);">
        Member since <?= date('M Y', strtotime($current_user['created_at'])) ?>
      </div>
      <hr style="margin:1.2rem 0;border:none;border-top:1px solid var(--line);">

      <?php if (flash('address_saved')): ?>
        <div class="alert alert-success" style="margin-bottom:1rem;font-size:.8rem;">Address saved.</div>
      <?php endif; ?>

      <form method="POST" action="/account/address">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom:.75rem;">
          <label style="font-size:.8rem;font-weight:600;">Shipping Address</label>
          <textarea name="address" class="form-control" rows="4"
                    placeholder="Enter your default shipping address"
                    style="font-size:.85rem;"><?= h($current_user['address'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-sm" style="width:100%;justify-content:center;">Save Address</button>
      </form>

      <hr style="margin:1.2rem 0;border:none;border-top:1px solid var(--line);">
      <a href="/logout" class="btn btn-outline btn-sm" style="width:100%;justify-content:center;">Sign Out</a>
    </div>

    <div>
      <h2 style="font-family:var(--font-display);font-size:1.3rem;margin-bottom:1rem;">Order History</h2>

      <?php if (!$orders): ?>
        <div class="card empty-state" style="padding:2rem;">
          <div class="icon">📦</div>
          <h3>No orders yet</h3>
          <p>
            <a href="/admin/categories" class="btn btn-primary" style="margin-top:.75rem;">Start Shopping</a>
          </p>
        </div>
      <?php else: ?>
        <div class="card" style="padding:0;overflow:hidden;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Order #</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $o): ?>
                <tr>
                  <td><strong>#<?= str_pad($o['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                  <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                  <td><?= $o['item_count'] ?> item<?= $o['item_count'] != 1 ? 's' : '' ?></td>
                  <td><strong><?= money($o['total']) ?></strong></td>
                  <td>
                    <span class="badge <?= $status_badges[$o['status']] ?? 'badge-neutral' ?>">
                      <?= ucfirst($o['status']) ?>
                    </span>
                  </td>
                  <td><a href="/order/confirm?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
