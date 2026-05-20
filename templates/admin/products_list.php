<?php // templates/admin/products_list.php ?>

<div class="admin-topbar">
  <h1>Products</h1>
  <div class="actions">
    <form method="get" action="/admin/products" class="d-inline-flex gap-1">
      <input type="text" name="search" value="<?= h($search ?? '') ?>"
             placeholder="Filter by name…" class="form-control" style="width:220px;">
      <button type="submit" class="btn btn-outline">Filter</button>
      <?php if (!empty($search)): ?>
        <a href="/admin/products" class="btn btn-outline">Clear</a>
      <?php endif; ?>
    </form>

    <div class="import-wrap" style="position:relative; display:inline-block;">
      <button type="button" class="btn btn-outline" onclick="document.getElementById('import-form-pop').classList.toggle('active')">
        <span>📤</span> Import
      </button>
      <div id="import-form-pop" class="card" style="position:absolute; top:100%; right:0; z-index:100; width:280px; margin-top:10px; display:none; padding:15px; box-shadow:var(--shadow);">
        <h4 style="margin-bottom:10px; font-size:14px;">Import Products (CSV)</h4>
        <form action="/admin/products/import" method="POST" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="file" name="csv_file" accept=".csv" class="form-control" required style="margin-bottom:10px;">
          <p class="text-xs text-muted mb-2">Columns: name, sku, price, stock, category_id</p>
          <button type="submit" class="btn btn-primary btn-sm w-100">Start Import</button>
        </form>
      </div>
    </div>

    <a href="/admin/products/new" class="btn btn-primary">+ Add Product</a>
  </div>
</div>

<div class="content">
  <?php if ($flash_msg): ?>
    <div class="alert alert-success"><?= h($flash_msg) ?></div>
  <?php endif; ?>

  <form action="/admin/products/batch" method="POST" id="batch-form">
    <?= csrf_field() ?>
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:40px"><input type="checkbox" class="select-all"></th>
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
            <td><input type="checkbox" name="ids[]" value="<?= $p->id ?>" class="row-checkbox"></td>
            <td>
              <?php product_img($p->image ?? '', $p->name, 'thumb', '', 'thumb', '60px') ?>
            </td>
          <td>
            <strong><?= h($p->name) ?></strong>
            <?php if ($p->featured): ?>
              <span title="Featured Product" class="ms-1" style="color:var(--gold);">★</span>
            <?php endif; ?>
          </td>
          <td><?= h($p->cat_name ?? '—') ?></td>
          <td>£<?= number_format($p->price, 2) ?></td>
          <td>
            <?php $stock = $p->getAvailableStock(); ?>
            <?php if ($stock == 0): ?>
              <span class="badge badge-danger"><?= $stock ?></span>
            <?php elseif ($stock <= settings()->low_stock_threshold): ?>
              <span class="badge badge-warning"><?= $stock ?></span>
            <?php else: ?>
              <span class="badge badge-success"><?= $stock ?></span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge <?= $p->active ? 'badge-success' : 'badge-neutral' ?>">
              <?= $p->active ? 'Active' : 'Inactive' ?>
            </span>
          </td>
          <td>
            <a href="/admin/products/edit?id=<?= $p->id ?>" class="btn btn-outline btn-sm">Edit</a>
            <form action="/admin/products/delete" method="POST" style="display:inline;" onsubmit="return confirm('Deactivate this product?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $p->id ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="batch-bar">
    <div class="batch-info">
      <span class="selected-count">0</span> products selected
    </div>
    <div class="batch-actions">
      <select name="action" class="form-control" style="width:auto;">
        <option value="">Choose action…</option>
        <option value="activate">Activate</option>
        <option value="deactivate">Deactivate</option>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Apply</button>
    </div>
  </div>
</form>
</div>
