<?php // templates/admin/delivery_list.php ?>
<div class="admin-topbar">
  <h1>Delivery Options</h1>
  <div class="actions">
    <a href="/admin/delivery/new" class="btn btn-primary">+ Add Option</a>
  </div>
</div>

<div class="content">
  <?php if ($msg = flash('success')): ?>
    <div class="alert alert-success"><?= h($msg) ?></div>
  <?php endif; ?>

  <table class="data-table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Price</th>
        <th>Min Order</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($options as $opt): ?>
        <tr>
          <td><strong><?= h($opt->name) ?></strong></td>
          <td><?= money($opt->price) ?></td>
          <td><?= money($opt->min_order_total) ?></td>
          <td>
            <span class="badge <?= $opt->active ? 'badge-success' : 'badge-neutral' ?>">
              <?= $opt->active ? 'Active' : 'Inactive' ?>
            </span>
          </td>
          <td>
            <a href="/admin/delivery/edit?id=<?= $opt->id ?>" class="btn btn-outline btn-sm">Edit</a>
            <a href="/admin/delivery/delete?id=<?= $opt->id ?>" class="btn btn-danger btn-sm"
               onclick="return confirm('Are you sure you want to delete this option?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($options)): ?>
        <tr><td colspan="5" class="text-center p-4" style="opacity:.5;">No delivery options found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
