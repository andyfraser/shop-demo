<?php // templates/admin/categories_list.php ?>

<div class="admin-topbar">
  <h1>Categories</h1>
  <div class="actions">
    <a href="/admin/categories/new" class="btn btn-primary">+ Add Category</a>
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
        <th>Parent</th>
        <th>Slug</th>
        <th>Products</th>
        <th>Description</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($categories as $c): ?>
        <tr>
          <td><strong><?= h($c->name) ?></strong></td>
          <td><?= $c->parent_name ? h($c->parent_name) : '<span style="opacity:.4">—</span>' ?></td>
          <td>
            <code style="font-size:.78rem;background:var(--sand);padding:.1rem .4rem;border-radius:2px;">
              <?= h($c->slug) ?>
            </code>
          </td>
          <td><?= $c->product_count ?></td>
          <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <?= h($c->description ?? '') ?>
          </td>
          <td>
            <a href="/admin/categories/edit?id=<?= $c->id ?>" class="btn btn-outline btn-sm">Edit</a>
            <a href="/admin/categories/delete?id=<?= $c->id ?>" class="btn btn-danger btn-sm"
               onclick="return confirm('Delete this category? Products will become uncategorised.')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
