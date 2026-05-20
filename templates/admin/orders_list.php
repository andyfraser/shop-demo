<?php // templates/admin/orders_list.php ?>

<div class="admin-topbar">
  <h1>Orders</h1>
  <div class="actions">
    <a href="/admin/orders/export<?= !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '' ?>" class="btn btn-outline btn-sm">
      <span>📥</span> Export CSV
    </a>
    <?php foreach ([''=>'All','pending'=>'Pending','confirmed'=>'Confirmed','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $s => $label): ?>
      <a href="?status=<?= $s ?>"
         class="btn <?= $filter === $s ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        <?= $label ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="content">
  <form action="/admin/orders/batch" method="POST" id="batch-form">
    <?= csrf_field() ?>
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:40px"><input type="checkbox" class="select-all"></th>
          <th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($orders): ?>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td><input type="checkbox" name="ids[]" value="<?= $o->id ?>" class="row-checkbox"></td>
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
          <td colspan="7" class="text-center p-4 text-muted">No orders found</td>
        </tr>
      <?php endif; ?>

    </tbody>
  </table>

  <div class="batch-bar">
    <div class="batch-info">
      <span class="selected-count">0</span> orders selected
    </div>
    <div class="batch-actions">
      <select name="status" class="form-control" style="width:auto;">
        <option value="">Set status to…</option>
        <option value="confirmed">Confirmed</option>
        <option value="shipped">Shipped</option>
        <option value="delivered">Delivered</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Update Status</button>
    </div>
  </div>
</form>
</div>
