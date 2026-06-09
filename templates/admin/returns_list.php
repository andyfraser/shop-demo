<?php // templates/admin/returns_list.php ?>

<div class="admin-topbar">
  <h1>Return Requests</h1>
</div>

<div class="content">
  <?php if ($flash_msg): ?>
    <div class="alert alert-success" style="margin-bottom:1.5rem;"><?= h($flash_msg) ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert alert-danger" style="margin-bottom:1.5rem;"><?= h($flash_error) ?></div>
  <?php endif; ?>

  <table class="data-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Order</th>
        <th>Customer</th>
        <th>Status</th>
        <th>Date</th>
        <th style="text-align:right;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($returns)): ?>
        <tr>
          <td colspan="6" style="text-align:center;padding:3rem;color:var(--ink-2);">No return requests found.</td>
        </tr>
      <?php else: ?>
        <?php foreach ($returns as $r): ?>
          <tr>
            <td>#<?= $r->id ?></td>
            <td><a href="/admin/orders/detail?id=<?= $r->order_id ?>"><strong>#<?= str_pad($r->order_id, 6, '0', STR_PAD_LEFT) ?></strong></a></td>
            <td><?= h($r->customer_name) ?></td>
            <td><?= new \App\View\Components\StatusBadge(ucfirst($r->status), $r->getStatusBadgeClass()) ?></td>
            <td><?= date('d M Y', strtotime($r->created_at)) ?></td>
            <td style="text-align:right;">
              <a href="/admin/returns/detail?id=<?= $r->id ?>" class="btn btn-outline btn-sm">View Details</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
