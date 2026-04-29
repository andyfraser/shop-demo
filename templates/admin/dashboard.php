<?php // templates/admin/dashboard.php ?>

<div class="admin-topbar">
  <h1>Dashboard</h1>
  <div class="actions">
    <a href="/admin/products/new" class="btn btn-primary">+ Add Product</a>
  </div>
</div>

<div class="content">
  <?php if ($low_stock): ?>
    <div class="alert alert-warning" style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem;">
      <span style="font-size:1.5rem;">⚠️</span>
      <div>
        <h4 style="margin:0;font-size:1rem;">Low Stock Warning</h4>
        <p style="margin:0;font-size:.85rem;color:var(--ink-2);">There are <?= count($low_stock) ?> products running low on stock. Please review the inventory below.</p>
      </div>
      <a href="/admin/products" class="btn btn-outline btn-sm" style="margin-left:auto;">Manage Products</a>
    </div>
  <?php endif; ?>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-val"><?= $stats['products'] ?></div>
      <div class="stat-label">Active Products</div>
    </div>
    <div class="stat-card">
      <div class="stat-val"><?= $stats['customers'] ?></div>
      <div class="stat-label">Customers</div>
    </div>
    <div class="stat-card">
      <div class="stat-val"><?= $stats['orders'] ?></div>
      <div class="stat-label">Total Orders</div>
    </div>
    <div class="stat-card">
      <div class="stat-val">£<?= number_format($stats['revenue'], 0) ?></div>
      <div class="stat-label">Revenue</div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 300px;gap:1.5rem;align-items:start;">
    <div>
      <div class="page-actions" style="margin-bottom:1rem;">
        <strong style="font-size:.9rem;">Recent Orders</strong>
        <a href="/admin/orders" class="btn btn-outline btn-sm">View All</a>
      </div>
      <table class="data-table">
        <thead>
          <tr>
            <th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($recent_orders): ?>
            <?php foreach ($recent_orders as $o): ?>
              <tr>
                <td><a href="/admin/orders/detail?id=<?= $o->id ?>"><?= $o->getFormattedId() ?></a></td>
                <td><?= h($o->user_name ?? 'Guest') ?></td>
                <td><strong>£<?= number_format($o->total, 2) ?></strong></td>
                <td>
                  <span class="badge <?= $o->getStatusBadgeClass() ?>"><?= ucfirst($o->status) ?></span>
                </td>
                <td><?= date('d M', strtotime($o->created_at)) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="text-align:center;padding:1.5rem;color:var(--ink-2);">No orders yet</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div>
      <div class="page-actions" style="margin-bottom:1rem;">
        <strong style="font-size:.9rem;">Low Stock</strong>
      </div>
      <div class="card" style="padding:0;overflow:hidden;">
        <?php if ($low_stock): ?>
          <?php foreach ($low_stock as $p): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.75rem 1rem;border-bottom:1px solid var(--line);">
              <div style="font-size:.85rem;font-weight:500;flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= h($p->name) ?>
              </div>
              <span class="badge <?= $p->stock == 0 ? 'badge-danger' : 'badge-warning' ?>"><?= $p->stock ?> left</span>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="padding:1.5rem;text-align:center;color:var(--ink-2);font-size:.85rem;">
            All products well stocked ✓
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
