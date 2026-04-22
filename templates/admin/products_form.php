<?php // templates/admin/products_form.php ?>

<div class="admin-topbar">
  <h1><?= $is_new ? 'Add Product' : 'Edit Product' ?></h1>
  <div class="actions">
    <a href="/admin/products" class="btn btn-outline">← Back</a>
  </div>
</div>

<div class="content">
  <?php if ($errors): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="card" style="max-width:800px;">
    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
      <?php if ($product_id): ?>
        <input type="hidden" name="id" value="<?= $product_id ?>">
      <?php endif; ?>

      <div class="form-grid">
        <div class="span-2">
          <div class="form-group">
            <label>Product Name *</label>
            <input type="text" name="name" class="form-control"
                   value="<?= h($product['name'] ?? '') ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label>Price (£) *</label>
          <input type="number" name="price" step="0.01" min="0.01" class="form-control"
                 value="<?= h($product['price'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label>VAT Rate (%) *</label>
          <input type="number" name="vat_rate" step="0.01" min="0" class="form-control"
                 value="<?= h($product['vat_rate'] ?? '20.00') ?>" required>
        </div>

        <div class="form-group">
          <label>Stock Quantity</label>
          <input type="number" name="stock" min="0" class="form-control"
                 value="<?= h($product['stock'] ?? 0) ?>">
        </div>

        <div class="form-group span-2">
          <label>Category</label>
          <select name="category_id" class="form-control">
            <option value="">— No category —</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"
                      <?= ($product['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                <?= $cat['parent_name'] ? h($cat['parent_name']) . ' › ' : '' ?><?= h($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="span-2">
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control"><?= h($product['description'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="span-2">
          <div class="form-group">
            <label>Product Image</label>
            <?php
              $img_file = $product['image'] ?? null;
              $img_url  = $img_file ? BASE_URL . '/public/images/' . h($img_file) : null;
            ?>
            <?php if ($img_url): ?>
              <div style="margin-bottom:.75rem;">
                <img src="<?= $img_url ?>" alt=""
                     style="height:140px;object-fit:cover;border-radius:var(--radius);display:block;">
                <label style="display:flex;align-items:center;gap:.4rem;margin-top:.5rem;font-weight:400;cursor:pointer;">
                  <input type="checkbox" name="remove_image" value="1">
                  Remove current image
                </label>
              </div>
              <input type="hidden" name="existing_image" value="<?= h($img_file) ?>">
            <?php endif; ?>
            <input type="file" name="image" class="form-control"
                   accept="image/jpeg,image/png,image/gif,image/webp"
                   style="padding:.4rem;">
            <div style="font-size:.75rem;color:var(--ink-2);margin-top:.35rem;">
              JPEG, PNG, GIF or WebP — max 5MB
            </div>
          </div>
        </div>

        <div class="span-2" style="display:flex;gap:1.5rem;">
          <label class="toggle-label">
            <input type="checkbox" name="active" value="1"
                   <?= ($product['active'] ?? 1) ? 'checked' : '' ?>>
            <span class="toggle-track"></span>
            Active (visible in store)
          </label>
          <label class="toggle-label">
            <input type="checkbox" name="featured" value="1"
                   <?= ($product['featured'] ?? 0) ? 'checked' : '' ?>>
            <span class="toggle-track"></span>
            Featured Product (shown on homepage)
          </label>
        </div>
      </div>

      <div style="display:flex;gap:.75rem;margin-top:1rem;">
        <button type="submit" name="save" class="btn btn-primary">
          <?= $is_new ? 'Create Product' : 'Save Changes' ?>
        </button>
        <a href="/admin/products" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>
