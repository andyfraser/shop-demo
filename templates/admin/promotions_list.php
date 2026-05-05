<?php // templates/admin/promotions_list.php ?>

<div class="admin-topbar">
  <h1>Promotions</h1>
  <div class="actions">
    <a href="/admin/promotions/new" class="btn btn-primary">+ Add Promotion</a>
  </div>
</div>

<div class="content">
  <?php if ($flash_msg): ?>
    <div class="alert alert-success"><?= h($flash_msg) ?></div>
  <?php endif; ?>

  <table class="data-table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Code</th>
        <th>Type</th>
        <th>Value</th>
        <th>Target</th>
        <th>Status</th>
        <th>Usage</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($promotions as $promo): ?>
        <tr>
          <td>
            <strong><?= h($promo->name) ?></strong>
            <?php if ($promo->description): ?>
              <div class="text-xs text-muted"><?= h($promo->description) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($promo->code): ?>
              <code class="text-sm"><?= h($promo->code) ?></code>
            <?php else: ?>
              <span class="text-xs text-muted">Automatic</span>
            <?php endif; ?>
          </td>
          <td class="text-sm"><?= ucfirst(str_replace('_', ' ', $promo->type)) ?></td>
          <td>
            <strong>
              <?php if ($promo->type === 'percentage'): ?>
                <?= (float)$promo->value ?>%
              <?php else: ?>
                <?= money($promo->value) ?>
              <?php endif; ?>
            </strong>
          </td>
          <td class="text-sm"><?= ucfirst($promo->target_type) ?></td>
          <td>
            <?php if ($promo->isActive()): ?>
              <span class="badge badge-success">Active</span>
            <?php else: ?>
              <span class="badge badge-neutral">Inactive</span>
            <?php endif; ?>
          </td>
          <td class="text-sm">
            <?= $promo->used_count ?> / <?= $promo->usage_limit ?: '∞' ?>
          </td>
          <td>
            <a href="/admin/promotions/edit?id=<?= $promo->id ?>" class="btn btn-outline btn-sm">Edit</a>
            <a href="/admin/promotions/delete?id=<?= $promo->id ?>" class="btn btn-danger btn-sm"
               onclick="return confirm('Delete this promotion?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($promotions)): ?>
        <tr>
          <td colspan="8" class="text-center" style="padding:3rem;">
            No promotions found. <a href="/admin/promotions/new">Create one?</a>
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
