<?php // templates/admin/orders_list.php ?>

<div class="admin-topbar">
  <h1>Orders</h1>
  <div class="actions">
    <?php foreach ([''=>'All','pending'=>'Pending','confirmed'=>'Confirmed','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $s => $label): ?>
      <a href="?status=<?= $s ?>"
         class="btn <?= $filter === $s ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        <?= $label ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="content">
  <table class="data-table">
    <thead>
      <tr>
        <th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($orders): ?>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><strong><?= $o->getFormattedId() ?></strong></td>
            <td><?= h($o->user_name ?? 'Guest') ?></td>
            <td>£<?= number_format($o->total, 2) ?></td>
            <td>
              <span class="badge <?= $o->getStatusBadgeClass() ?>">
                <?= ucfirst($o->status) ?>
              </span>
            </td>
            <td><?= date('d M Y, H:i', strtotime($o->created_at)) ?></td>
            <td><a href="/admin/orders/detail?id=<?= $o->id ?>" class="btn btn-outline btn-sm">View</a></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="6" style="text-align:center;padding:2rem;color:var(--ink-2);">No orders found</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
