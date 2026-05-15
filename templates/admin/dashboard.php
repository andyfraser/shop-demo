<?php // templates/admin/dashboard.php ?>

<div class="admin-topbar">
  <h1>Dashboard</h1>
  <div class="actions">
    <a href="/admin/products/new" class="btn btn-primary">+ Add Product</a>
  </div>
</div>

<div class="content">
  <?php if ($low_stock): ?>
    <?= (new \App\View\Components\Alert(
        'There are ' . count($low_stock) . ' products running low on stock. Please review the inventory below.',
        'warning'
    ))->render() ?>
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

  <div class="grid-sidebar">
    <div>
      <div class="page-actions mb-2">
        <strong class="text-sm">Recent Orders</strong>
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
                  <?= (new \App\View\Components\StatusBadge(ucfirst($o->status), $o->getStatusBadgeClass()))->render() ?>
                </td>
                <td><?= date('d M', strtotime($o->created_at)) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center text-muted" style="padding:1.5rem;">No orders yet</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div>
      <div class="page-actions mb-2">
        <strong class="text-sm">Low Stock</strong>
      </div>
      <div class="card p-0 overflow-hidden">
        <?php if ($low_stock): ?>
          <?php foreach ($low_stock as $p): ?>
            <div class="flex-between border-bottom" style="padding:.75rem 1rem;">
              <div class="text-sm font-bold nowrap" style="flex:1;min-width:0;">
                <?= h($p->name) ?>
              </div>
              <?= (new \App\View\Components\StatusBadge(
                  $p->stock . ' left', 
                  $p->stock == 0 ? 'badge-danger' : 'badge-warning'
              ))->render() ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-center text-muted text-sm" style="padding:1.5rem;">
            All products well stocked ✓
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
