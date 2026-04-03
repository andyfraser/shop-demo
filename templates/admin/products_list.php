<?php // templates/admin/products_list.php ?>

<div class="admin-topbar">
  <h1>Products</h1>
  <div class="actions">
    <form method="get" action="/admin/products" style="display:inline-flex;gap:.5rem;">
      <input type="text" name="search" value="<?= h($search ?? '') ?>"
             placeholder="Filter by name…" class="form-control" style="width:220px;">
      <button type="submit" class="btn btn-outline">Filter</button>
      <?php if (!empty($search)): ?>
        <a href="/admin/products" class="btn btn-outline">Clear</a>
      <?php endif; ?>
    </form>
    <a href="/admin/products/new" class="btn btn-primary">+ Add Product</a>
  </div>
</div>

<div class="content">
  <?php if ($flash_msg): ?>
    <div class="alert alert-success"><?= h($flash_msg) ?></div>
  <?php endif; ?>

  <table class="data-table">
    <thead>
      <tr>
        <th style="width:60px"></th>
        <th>Name</th>
        <th>Category</th>
        <th>Price</th>
        <th>Stock</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($products as $p): ?>
        <tr>
          <td>
            <?php product_img($p['image'] ?? '', $p['name'], 'thumb') ?>
          </td>
          <td><strong><?= h($p['name']) ?></strong></td>
          <td><?= h($p['cat_name'] ?? '—') ?></td>
          <td>£<?= number_format($p['price'], 2) ?></td>
          <td>
            <?php if ($p['stock'] == 0): ?>
              <span class="badge badge-danger"><?= $p['stock'] ?></span>
            <?php elseif ($p['stock'] <= 5): ?>
              <span class="badge badge-warning"><?= $p['stock'] ?></span>
            <?php else: ?>
              <span class="badge badge-success"><?= $p['stock'] ?></span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge <?= $p['active'] ? 'badge-success' : 'badge-neutral' ?>">
              <?= $p['active'] ? 'Active' : 'Inactive' ?>
            </span>
          </td>
          <td>
            <a href="/admin/products/edit?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <a href="/admin/products/delete?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
               onclick="return confirm('Deactivate this product?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
