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

  <?php
    // Helper to handle both object and array during transition or error repopulation
    $get = fn($key) => is_object($product) ? ($product->$key ?? null) : ($product[$key] ?? null);
  ?>

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
                   value="<?= h($get('name') ?? '') ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label>SKU</label>
          <input type="text" name="sku" class="form-control"
                 value="<?= h($get('sku') ?? '') ?>">
        </div>

        <div class="form-group">
          <label>Base Price (£) *</label>
          <input type="number" name="price" step="0.01" min="0.01" class="form-control"
                 value="<?= h($get('price') ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label>VAT Rate (%) *</label>
          <input type="number" name="vat_rate" step="0.01" min="0" class="form-control"
                 value="<?= h($get('vat_rate') ?? '20.00') ?>" required>
        </div>

        <div class="form-group">
          <label>Base Stock Quantity</label>
          <input type="number" name="stock" min="0" class="form-control"
                 value="<?= h($get('stock') ?? 0) ?>">
        </div>

        <div class="form-group span-2">
          <label>Category</label>
          <select name="category_id" class="form-control">
            <option value="">— No category —</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat->id ?>"
                      <?= ($get('category_id') ?? '') == $cat->id ? 'selected' : '' ?>>
                <?= $cat->parent_name ? h($cat->parent_name) . ' › ' : '' ?><?= h($cat->name) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="span-2">
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control"><?= h($get('description') ?? '') ?></textarea>
          </div>
        </div>

        <div class="span-2">
          <div class="form-group">
            <label>Product Image</label>
            <?php
              $img_file = $get('image');
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
                   <?= ($get('active') ?? 1) ? 'checked' : '' ?>>
            <span class="toggle-track"></span>
            Active (visible in store)
          </label>
          <label class="toggle-label">
            <input type="checkbox" name="featured" value="1"
                   <?= ($get('featured') ?? 0) ? 'checked' : '' ?>>
            <span class="toggle-track"></span>
            Featured Product (shown on homepage)
          </label>
          <label class="toggle-label">
            <input type="checkbox" name="force_variant" value="1"
                   <?= ($get('force_variant') ?? 0) ? 'checked' : '' ?>>
            <span class="toggle-track"></span>
            Force Variant Selection
          </label>
        </div>

        <div class="span-2" style="margin-top: 1rem;">
          <h3 style="font-family: var(--font-display); font-size: 1.2rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--line); padding-bottom: 0.5rem;">
            Product Variants
          </h3>
          <p style="font-size: 0.85rem; color: var(--ink-2); margin-bottom: 1rem;">
            Add variations like size or color. Price override is optional; leave empty to use base price.
          </p>

          <table class="table" id="variants-table" style="width: 100%;">
            <thead>
              <tr>
                <th>Variant Name</th>
                <th>SKU</th>
                <th>Price Override</th>
                <th>Stock</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php 
                $variants = is_object($product) ? ($product->variants ?? []) : [];
                foreach ($variants as $index => $v): 
              ?>
                <tr>
                  <td>
                    <input type="hidden" name="variants[<?= $index ?>][id]" value="<?= $v->id ?>">
                    <input type="text" name="variants[<?= $index ?>][name]" class="form-control" value="<?= h($v->name) ?>" placeholder="e.g. Large">
                  </td>
                  <td><input type="text" name="variants[<?= $index ?>][sku]" class="form-control" value="<?= h($v->sku ?? '') ?>" placeholder="SKU"></td>
                  <td><input type="number" name="variants[<?= $index ?>][price]" step="0.01" class="form-control" value="<?= h($v->price ?? '') ?>" placeholder="<?= h($get('price')) ?>"></td>
                  <td><input type="number" name="variants[<?= $index ?>][stock]" class="form-control" value="<?= h($v->stock) ?>"></td>
                  <td>
                    <label style="color: var(--accent); cursor: pointer; font-size: 0.8rem;">
                      <input type="checkbox" name="variants[<?= $index ?>][delete]" value="1"> Remove
                    </label>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <button type="button" class="btn btn-outline btn-sm" id="add-variant" style="margin-top: 0.5rem;">
            + Add Variant
          </button>
        </div>
      </div>

      <div style="display:flex;gap:.75rem;margin-top:2rem;">
        <button type="submit" name="save" class="btn btn-primary">
          <?= $is_new ? 'Create Product' : 'Save Changes' ?>
        </button>
        <a href="/admin/products" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('add-variant');
    const tbody = document.querySelector('#variants-table tbody');
    let index = <?= count($variants ?? []) ?>;

    btn.addEventListener('click', function() {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <input type="text" name="variants[${index}][name]" class="form-control" placeholder="e.g. Large">
            </td>
            <td><input type="text" name="variants[${index}][sku]" class="form-control" placeholder="SKU"></td>
            <td><input type="number" name="variants[${index}][price]" step="0.01" class="form-control" placeholder="Override"></td>
            <td><input type="number" name="variants[${index}][stock]" class="form-control" value="0"></td>
            <td></td>
        `;
        tbody.appendChild(tr);
        index++;
    });
});
</script>
