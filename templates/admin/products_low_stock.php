<?php // templates/admin/products_low_stock.php ?>

<div class="admin-topbar">
  <h1>Low Stock Management</h1>
  <div class="actions">
    <a href="/admin/products" class="btn btn-outline">Back to Products</a>
  </div>
</div>

<div class="content">
  <?php if ($flash_msg): ?>
    <?= (new \App\View\Components\Alert($flash_msg, 'success'))->render() ?>
  <?php endif; ?>

  <div class="card">
    <form action="/admin/products/low-stock/update" method="POST">
      <?= csrf_field() ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Product / Variant</th>
            <th width="120">Current Stock</th>
            <th width="150">New Stock</th>
            <th width="100">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($items): ?>
            <?php foreach ($items as $item): ?>
              <tr>
                <td>
                  <div class="font-bold"><?= h($item->name) ?></div>
                  <?php if ($item instanceof \App\Models\ProductVariant): ?>
                    <small class="text-muted">Variant of Product: <?= h($item->product_name) ?></small>
                  <?php else: ?>
                    <small class="text-muted">Standalone Product</small>
                  <?php endif; ?>
                </td>
                <td>
                  <?= (new \App\View\Components\StatusBadge(
                      $item->stock . ' left', 
                      $item->stock == 0 ? 'badge-danger' : 'badge-warning'
                  ))->render() ?>
                </td>
                <td>
                  <input type="number" 
                         name="<?= $item instanceof \App\Models\ProductVariant ? 'variant_stock' : 'stock' ?>[<?= $item->id ?>]" 
                         value="<?= $item->stock ?>" 
                         min="0" 
                         class="form-control" 
                         style="width: 100px;">
                </td>
                <td>
                  <a href="/admin/products/edit?id=<?= $item instanceof \App\Models\ProductVariant ? $item->product_id : $item->id ?>&return_to=/admin/products/low-stock" 
                     class="btn btn-sm btn-outline">Edit Details</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="text-center text-muted" style="padding:2rem;">
                No products are currently low on stock.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <?php if ($items): ?>
        <div class="mt-2 flex-end">
          <button type="submit" class="btn btn-primary">Update All Stock Levels</button>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>
