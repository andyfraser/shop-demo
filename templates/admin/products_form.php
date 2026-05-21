<?php // templates/admin/products_form.php ?>

<div class="admin-topbar">
  <h1><?= $is_new ? 'Add Product' : 'Edit Product' ?></h1>
  <div class="actions">
    <a href="<?= $return_to ?? '/admin/products' ?>" class="btn btn-outline">← Back</a>
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
    $get = fn($key) => isset($product) && $product ? (is_object($product) ? ($product->$key ?? null) : ($product[$key] ?? null)) : null;
  ?>

  <div class="card card-lg" style="max-width:1100px;">
    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
      <?php if ($product_id): ?>
        <input type="hidden" name="id" value="<?= $product_id ?>">
      <?php endif; ?>
      <?php if (isset($return_to)): ?>
        <input type="hidden" name="return_to" value="<?= h($return_to) ?>">
      <?php endif; ?>

      <div class="form-grid">
        <div class="span-2" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <div>
                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="name" class="form-control"
                        value="<?= h($get('name') ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" style="min-height: 150px;"><?= h($get('description') ?? '') ?></textarea>
                </div>
            </div>

            <div>
                <div class="form-group">
                    <label>SKU</label>
                    <input type="text" name="sku" class="form-control"
                        value="<?= h($get('sku') ?? '') ?>">
                </div>

                <div class="form-grid">
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
                </div>

                <div class="form-group">
                    <label>Base Stock Quantity</label>
                    <input type="number" name="stock" min="0" class="form-control"
                        value="<?= h($get('stock') ?? 0) ?>">
                </div>

                <div class="form-group">
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
            </div>
        </div>

        <div class="span-2">
          <div class="form-group">
            <label>Product Image</label>
            <?php
              $img_file = $get('image');
              $img_url  = $img_file ? product_img_url($img_file, 'thumb') : null;
            ?>
            <div class="flex gap-3 flex-start">
                <?php if ($img_url): ?>
                <div style="flex-shrink: 0;">
                    <?php product_img($img_file, '', '', 'height:140px;width:140px;object-fit:cover;border-radius:var(--radius);display:block;border:1px solid var(--line);', 'thumb', '140px') ?>
                    <label class="flex-center gap-1 mt-1 text-sm" style="font-weight:400;cursor:pointer;">
                    <input type="checkbox" name="remove_image" value="1">
                    Remove image
                    </label>
                </div>
                <input type="hidden" name="existing_image" value="<?= h($img_file) ?>">
                <?php endif; ?>
                <div style="flex: 1;">
                    <input type="file" name="image" class="form-control"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        style="padding:.4rem;">
                    <div class="form-hint">
                    JPEG, PNG, GIF or WebP — max 5MB
                    </div>
                </div>
            </div>
          </div>
        </div>

        <div class="span-2 flex gap-3 bg-sand mb-2" style="padding:1rem;border-radius:var(--radius);">
          <label class="toggle-label">
            <input type="checkbox" name="active" value="1"
                   <?= ($get('active') ?? 1) ? 'checked' : '' ?>>
            <span class="toggle-track"></span>
            Active
          </label>
          <label class="toggle-label">
            <input type="checkbox" name="featured" value="1"
                   <?= ($get('featured') ?? 0) ? 'checked' : '' ?>>
            <span class="toggle-track"></span>
            Featured
          </label>
          <label class="toggle-label">
            <input type="checkbox" name="force_variant" value="1"
                   <?= ($get('force_variant') ?? 0) ? 'checked' : '' ?>>
            <span class="toggle-track"></span>
            Force Variant
          </label>
          <label class="toggle-label">
            <input type="checkbox" name="is_bundle" value="1" id="is-bundle-toggle"
                   <?= ($get('is_bundle') ?? 0) ? 'checked' : '' ?>>
            <span class="toggle-track"></span>
            Product Bundle
          </label>
          <label class="toggle-label">
            <input type="checkbox" name="is_virtual" value="1" id="is-virtual-toggle"
                   <?= ($get('is_virtual') ?? 0) ? 'checked' : '' ?>>
            <span class="toggle-track"></span>
            Virtual Product
          </label>
        </div>

        <div class="span-2 mt-2" id="virtual-product-section" style="<?= ($get('is_virtual') ?? 0) ? '' : 'display:none;' ?>">
          <h3 class="section-title border-bottom">
            Virtual Product Settings
          </h3>
          <div class="grid-2">
            <div class="form-group">
              <label for="virtual-type" class="form-label font-bold text-sm">Virtual Type</label>
              <select name="virtual_type" id="virtual-type" class="form-control">
                <option value="">— Select Type —</option>
                <option value="file" <?= ($get('virtual_type') === 'file') ? 'selected' : '' ?>>📁 Digital Download / File</option>
                <option value="giftcard" <?= ($get('virtual_type') === 'giftcard') ? 'selected' : '' ?>>🎁 Emailable Gift Card</option>
                <option value="license" <?= ($get('virtual_type') === 'license') ? 'selected' : '' ?>>🔑 Software License Key</option>
                <option value="membership" <?= ($get('virtual_type') === 'membership') ? 'selected' : '' ?>>👑 User Membership</option>
                <option value="event_ticket" <?= ($get('virtual_type') === 'event_ticket') ? 'selected' : '' ?>>🎟️ Event Ticket / Booking</option>
              </select>
            </div>
            
            <div class="form-group" id="file-path-group" style="<?= ($get('virtual_type') === 'file') ? '' : 'display:none;' ?>">
              <label for="file-path" class="form-label font-bold text-sm">Secure File Path</label>
              <input type="text" name="file_path" id="file-path" class="form-control" placeholder="e.g. storage/downloads/ebook.pdf" value="<?= h($get('file_path') ?? '') ?>">
              <div class="mt-1" style="display: flex; flex-direction: column; gap: 0.25rem;">
                <label for="virtual-file" class="form-label font-semibold text-xs text-muted" style="margin: 0.25rem 0 0 0;">Or Upload File Directly</label>
                <input type="file" name="virtual_file" id="virtual-file" class="form-control" style="padding: .4rem;">
              </div>
              <small class="text-muted" style="font-size:0.75rem; display:block; margin-top:0.25rem;">Select an existing path relative to project root, or upload a new file directly to storage/downloads.</small>
            </div>

            <div class="form-group" id="granted-role-group" style="<?= ($get('virtual_type') === 'membership') ? '' : 'display:none;' ?>">
              <label for="granted-role" class="form-label font-bold text-sm">Granted User Role</label>
              <select name="granted_role" id="granted-role" class="form-control">
                <option value="">— Select Role to Grant —</option>
                <option value="customer" <?= ($get('granted_role') === 'customer') ? 'selected' : '' ?>>Customer</option>
                <option value="vip" <?= ($get('granted_role') === 'vip') ? 'selected' : '' ?>>VIP User</option>
                <option value="wholesale" <?= ($get('granted_role') === 'wholesale') ? 'selected' : '' ?>>Wholesale Client</option>
              </select>
              <small class="text-muted" style="font-size:0.75rem; display:block; margin-top:0.25rem;">The role assigned to the user instantly upon order confirmation.</small>
            </div>
          </div>
        </div>

        <div class="span-2 mt-2" id="bundle-items-section" style="<?= ($get('is_bundle') ?? 0) ? '' : 'display:none;' ?>">
          <h3 class="section-title border-bottom">
            Bundle Components
          </h3>
          <p class="text-sm text-muted mb-2">
            Select the products that make up this bundle. Inventory will be subtracted from these items when the bundle is sold.
          </p>
          <div id="bundle-items-container">
            <table class="w-100" id="bundle-items-table" style="max-width: 600px; border-collapse: collapse;">
                <thead>
                    <tr class="text-xs text-muted font-bold" style="text-align: left; border-bottom: 1px solid var(--line);">
                        <th style="padding: 0.5rem 0;">Product</th>
                        <th style="padding: 0.5rem 0; width: 100px;">Quantity</th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                      $bundle_items = is_object($product) ? ($product->bundle_items ?? []) : ($post['bundle_items'] ?? []);
                      foreach ($bundle_items as $bIndex => $bi): 
                    ?>
                        <tr>
                            <td style="padding: 0.5rem 0; padding-right: 1rem;">
                                <select name="bundle_items[<?= $bIndex ?>][product_id]" class="form-control" required>
                                    <option value="">— Select Product —</option>
                                    <?php foreach ($all_products as $ap): ?>
                                        <?php if ($ap->id != $product_id): ?>
                                            <option value="<?= $ap->id ?>" <?= $bi['product_id'] == $ap->id ? 'selected' : '' ?>>
                                                <?= h($ap->name) ?> (SKU: <?= h($ap->sku) ?>)
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="padding: 0.5rem 0; padding-right: 1rem;">
                                <input type="number" name="bundle_items[<?= $bIndex ?>][qty]" class="form-control" value="<?= h($bi['qty'] ?? 1) ?>" min="1" required>
                            </td>
                            <td style="padding: 0.5rem 0;">
                                <button type="button" class="btn btn-outline btn-sm" onclick="this.closest('tr').remove()" style="color: var(--accent); border-color: transparent;">×</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" class="btn btn-outline btn-sm mt-1" id="add-bundle-item">
                + Add Component
            </button>
          </div>
        </div>

        <div class="span-2 mt-2">
          <h3 class="section-title border-bottom">
            Quantity Tiers
          </h3>
          <p class="text-sm text-muted mb-2">
            Add fixed discounts for bulk quantities. These will be subtracted from the base or variant price.
          </p>
          <div id="tiers-container">
            <table class="w-100" id="tiers-table" style="max-width: 500px; border-collapse: collapse;">
                <thead>
                    <tr class="text-xs text-muted font-bold" style="text-align: left; border-bottom: 1px solid var(--line);">
                        <th style="padding: 0.5rem 0;">Min. Quantity</th>
                        <th style="padding: 0.5rem 0;">Discount Amount (£)</th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                      $tiers = is_object($product) ? ($product->tiers ?? []) : ($post['tiers'] ?? []);
                      foreach ($tiers as $tIndex => $tier): 
                    ?>
                        <tr>
                            <td style="padding: 0.5rem 0; padding-right: 1rem;">
                                <input type="number" name="tiers[<?= $tIndex ?>][min_qty]" class="form-control" value="<?= h($tier['min_qty']) ?>" min="2" required>
                            </td>
                            <td style="padding: 0.5rem 0; padding-right: 1rem;">
                                <input type="number" name="tiers[<?= $tIndex ?>][discount]" step="0.01" min="0.01" class="form-control" value="<?= h($tier['discount']) ?>" required>
                            </td>
                            <td style="padding: 0.5rem 0;">
                                <button type="button" class="btn btn-outline btn-sm" onclick="this.closest('tr').remove()" style="color: var(--accent); border-color: transparent;">×</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" class="btn btn-outline btn-sm mt-1" id="add-tier">
                + Add Tier
            </button>
          </div>
        </div>

        <div class="span-2 mt-2">
          <h3 class="section-title border-bottom">
            Product Attributes
          </h3>
          <p class="text-sm text-muted mb-2">
            Manage attributes for filtering and variants. Select an attribute from the list to edit its values.
          </p>
          
          <div class="attr-master-detail">
            <div class="attr-master">
                <div class="attr-master-search">
                    <div class="attr-search-container">
                        <span class="attr-search-icon">🔍</span>
                        <input type="text" id="attr-search" class="form-control text-sm" placeholder="Search attributes..." style="padding: 0.4rem 0.6rem;">
                    </div>
                </div>
                <div class="attr-list" id="attr-master-list">
                    <?php foreach ($all_attributes as $attr): ?>
                        <div class="attr-item" data-id="<?= $attr['id'] ?>">
                            <span><?= h($attr['name']) ?></span>
                            <span class="badge badge-neutral attr-count-badge">0</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="attr-detail" id="attr-detail-container">
                <div class="attr-detail-empty">
                    <div class="ico">🔧</div>
                    <h4>No Attribute Selected</h4>
                    <p>Select an attribute from the list to manage its values and variant settings.</p>
                </div>

                <?php 
                  $variant_attr_ids = is_object($product) ? ($product->variant_attribute_ids ?? []) : ($_POST['variant_attribute_ids'] ?? []);
                  foreach ($all_attributes as $attr): 
                ?>
                    <div class="attr-group" id="attr-group-<?= $attr['id'] ?>" data-attr-id="<?= $attr['id'] ?>" data-attr-name="<?= h($attr['name']) ?>" style="display: none;">
                        <h3 class="font-display mb-1"><?= h($attr['name']) ?></h3>
                        
                        <div class="bg-sand mb-3" style="padding: 1rem; border-radius: var(--radius);">
                            <label class="toggle-label text-sm">
                                <input type="checkbox" name="variant_attribute_ids[]" value="<?= $attr['id'] ?>" class="use-as-variant-checkbox"
                                    <?= in_array($attr['id'], $variant_attr_ids) ? 'checked' : '' ?>>
                                <span class="toggle-track"></span>
                                <strong>Use as Variant</strong> — Enables this attribute in the variants table below.
                            </label>
                        </div>

                        <h4 class="text-sm mb-1 border-bottom" style="padding-bottom: 0.25rem;">Available Values</h4>
                        <div class="attr-values-box">
                            <?php foreach ($attr['values'] as $val): ?>
                                <label class="attr-val-label" data-val-id="<?= $val['id'] ?>" data-val-name="<?= h($val['value']) ?>">
                                    <input type="checkbox" name="attribute_value_ids[]" value="<?= $val['id'] ?>" class="attr-val-checkbox"
                                        <?= in_array($val['id'], $product_attribute_ids) ? 'checked' : '' ?>>
                                    <?= h($val['value']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="span-2 mt-2">
          <h3 class="section-title border-bottom">
            Product Variants
          </h3>
          <p class="text-sm text-muted mb-2">
            Add variations like size or color. If using variant attributes, select them from the dropdowns. 
            Variant Name is optional if attributes are selected.
          </p>

          <div style="overflow-x: auto;">
            <table class="w-100" id="variants-table" style="min-width: 800px; border-collapse: collapse;">
              <thead>
                <tr id="variant-header-row" class="border-bottom" style="text-align: left;">
                  <th style="width: 40px;"></th>
                  <!-- Dynamic columns will be inserted here -->
                  <th class="text-xs text-muted font-bold" style="padding: 0.5rem 0;">Variant Name / Label</th>
                  <th class="text-xs text-muted font-bold" style="padding: 0.5rem 0; width: 140px;">SKU</th>
                  <th class="text-xs text-muted font-bold" style="padding: 0.5rem 0; width: 120px;">Price Override</th>
                  <th class="text-xs text-muted font-bold" style="padding: 0.5rem 0; width: 80px;">Stock</th>
                  <th class="text-xs text-muted font-bold variant-virtual-col" style="padding: 0.5rem 0; width: 180px; <?= ($get('is_virtual') ?? 0) ? '' : 'display:none;' ?>">Digital File Path</th>
                  <th class="text-xs text-muted font-bold variant-virtual-col" style="padding: 0.5rem 0; width: 140px; <?= ($get('is_virtual') ?? 0) ? '' : 'display:none;' ?>">Grant Role</th>
                  <th style="width: 80px;"></th>
                </tr>
              </thead>
              <tbody>
                <?php 
                  $variants = is_object($product) ? ($product->variants ?? []) : [];
                  foreach ($variants as $index => $v): 
                ?>
                  <tr draggable="true" class="variant-row" data-index="<?= $index ?>">
                    <td class="drag-handle" title="Drag to reorder" style="padding: 0.5rem 0;">⋮⋮</td>
                    <!-- Dynamic values will be inserted here by JS -->
                    <td style="padding: 0.5rem 0; padding-right: 1rem;">
                      <input type="hidden" name="variants[<?= $index ?>][id]" value="<?= $v->id ?>">
                      <input type="hidden" name="variants[<?= $index ?>][sort_order]" class="sort-order-input" value="<?= $v->sort_order ?>">
                      <input type="text" name="variants[<?= $index ?>][name]" class="form-control" value="<?= h($v->name) ?>" placeholder="e.g. Blue Small">
                    </td>
                    <td style="padding: 0.5rem 0; padding-right: 1rem;"><input type="text" name="variants[<?= $index ?>][sku]" class="form-control" value="<?= h($v->sku ?? '') ?>" placeholder="SKU"></td>
                    <td style="padding: 0.5rem 0; padding-right: 1rem;"><input type="number" name="variants[<?= $index ?>][price]" step="0.01" class="form-control" value="<?= h($v->price ?? '') ?>" placeholder="<?= h($get('price')) ?>"></td>
                    <td style="padding: 0.5rem 0; padding-right: 1rem;"><input type="number" name="variants[<?= $index ?>][stock]" class="form-control" value="<?= h($v->stock) ?>"></td>
                    <td class="variant-virtual-col" style="padding: 0.5rem 0; padding-right: 1rem; <?= ($get('is_virtual') ?? 0) ? '' : 'display:none;' ?>">
                      <input type="text" name="variants[<?= $index ?>][file_path]" class="form-control" value="<?= h($v->file_path ?? '') ?>" placeholder="e.g. storage/downloads/file.zip">
                    </td>
                    <td class="variant-virtual-col" style="padding: 0.5rem 0; padding-right: 1rem; <?= ($get('is_virtual') ?? 0) ? '' : 'display:none;' ?>">
                      <select name="variants[<?= $index ?>][granted_role]" class="form-control">
                        <option value="">— None —</option>
                        <option value="customer" <?= ($v->granted_role === 'customer') ? 'selected' : '' ?>>Customer</option>
                        <option value="vip" <?= ($v->granted_role === 'vip') ? 'selected' : '' ?>>VIP User</option>
                        <option value="wholesale" <?= ($v->granted_role === 'wholesale') ? 'selected' : '' ?>>Wholesale Client</option>
                      </select>
                    </td>
                    <td style="padding: 0.5rem 0;">
                      <label class="text-sm" style="color: var(--accent); cursor: pointer;">
                        <input type="checkbox" name="variants[<?= $index ?>][delete]" value="1"> Remove
                      </label>
                    </td>
                    <?php 
                        // We need to output the current attribute values as hidden inputs so JS can pick them up and populate the dynamic selects
                        foreach ($v->attribute_value_ids as $valId):
                    ?>
                        <input type="hidden" class="v-hidden-attr-val" value="<?= $valId ?>">
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <button type="button" class="btn btn-outline btn-sm mt-1" id="add-variant">
            + Add Variant
          </button>
        </div>
      </div>

      <div class="flex gap-2 mt-4">
        <button type="submit" name="save" class="btn btn-primary">
          <?= $is_new ? 'Create Product' : 'Save Changes' ?>
        </button>
        <a href="<?= $return_to ?? '/admin/products' ?>" class="btn btn-outline">Cancel</a>
      </div>    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ── Attribute Master-Detail Logic ──────────────────────────────────────
    const attrItems = document.querySelectorAll('.attr-item');
    const attrGroups = document.querySelectorAll('.attr-group');
    const detailEmpty = document.querySelector('.attr-detail-empty');
    const attrSearch = document.getElementById('attr-search');

    function updateAttrCounts() {
        attrItems.forEach(item => {
            const id = item.dataset.id;
            const group = document.getElementById('attr-group-' + id);
            const count = group.querySelectorAll('.attr-val-checkbox:checked').length;
            const badge = item.querySelector('.attr-count-badge');
            badge.textContent = count;
            badge.className = count > 0 ? 'badge badge-success' : 'badge badge-neutral';
            
            if (count > 0) {
                item.classList.add('has-selection');
            } else {
                item.classList.remove('has-selection');
            }
        });
    }

    attrItems.forEach(item => {
        item.addEventListener('click', () => {
            attrItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            
            attrGroups.forEach(g => g.style.display = 'none');
            detailEmpty.style.display = 'none';
            
            const target = document.getElementById('attr-group-' + item.dataset.id);
            if (target) target.style.display = 'block';
        });
    });

    if (attrSearch) {
        attrSearch.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            attrItems.forEach(item => {
                const name = item.querySelector('span').textContent.toLowerCase();
                item.style.display = name.includes(term) ? 'flex' : 'none';
            });
        });
    }

    document.querySelectorAll('.attr-val-checkbox').forEach(cb => {
        cb.addEventListener('change', updateAttrCounts);
    });

    updateAttrCounts();

    // ── Bundle Items Logic ────────────────────────────────────────────────
    const isBundleToggle = document.getElementById('is-bundle-toggle');
    const bundleSection = document.getElementById('bundle-items-section');
    const addBundleItemBtn = document.getElementById('add-bundle-item');
    const bundleItemsTbody = document.querySelector('#bundle-items-table tbody');
    let bundleItemIndex = <?= count($bundle_items ?? []) ?>;

    if (isBundleToggle) {
        isBundleToggle.addEventListener('change', function() {
            bundleSection.style.display = this.checked ? 'block' : 'none';
        });
    }

    if (addBundleItemBtn) {
        const productOptions = `
            <option value="">— Select Product —</option>
            <?php foreach ($all_products as $ap): ?>
                <?php if ($ap->id != $product_id): ?>
                    <option value="<?= $ap->id ?>"><?= h($ap->name) ?> (SKU: <?= h($ap->sku) ?>)</option>
                <?php endif; ?>
            <?php endforeach; ?>
        `;

        addBundleItemBtn.addEventListener('click', function() {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="padding: 0.5rem 0; padding-right: 1rem;">
                    <select name="bundle_items[${bundleItemIndex}][product_id]" class="form-control" required>
                        ${productOptions}
                    </select>
                </td>
                <td style="padding: 0.5rem 0; padding-right: 1rem;">
                    <input type="number" name="bundle_items[${bundleItemIndex}][qty]" class="form-control" value="1" min="1" required>
                </td>
                <td style="padding: 0.5rem 0;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="this.closest('tr').remove()" style="color: var(--accent); border-color: transparent;">×</button>
                </td>
            `;
            bundleItemsTbody.appendChild(tr);
            bundleItemIndex++;
        });
    }

    // ── Tiers Table Logic ──────────────────────────────────────────────────
    const addTierBtn = document.getElementById('add-tier');
    const tiersTbody = document.querySelector('#tiers-table tbody');
    let tierIndex = <?= count($tiers ?? []) ?>;

    if (addTierBtn) {
        addTierBtn.addEventListener('click', function() {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="padding: 0.5rem 0; padding-right: 1rem;">
                    <input type="number" name="tiers[${tierIndex}][min_qty]" class="form-control" value="" min="2" required>
                </td>
                <td style="padding: 0.5rem 0; padding-right: 1rem;">
                    <input type="number" name="tiers[${tierIndex}][discount]" step="0.01" min="0.01" class="form-control" value="" required>
                </td>
                <td style="padding: 0.5rem 0;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="this.closest('tr').remove()" style="color: var(--accent); border-color: transparent;">×</button>
                </td>
            `;
            tiersTbody.appendChild(tr);
            tierIndex++;
        });
    }

    // ── Variants Table Logic ───────────────────────────────────────────────
    const btn = document.getElementById('add-variant');
    const tbody = document.querySelector('#variants-table tbody');
    const headerRow = document.getElementById('variant-header-row');
    let index = <?= count($variants ?? []) ?>;

    // Map to keep track of dynamic columns
    let activeVariantAttrs = [];

    function updateVariantColumns() {
        // 1. Identify which attributes are marked "Use as Variant"
        const newActiveAttrs = [];
        document.querySelectorAll('.use-as-variant-checkbox:checked').forEach(cb => {
            const group = cb.closest('.attr-group');
            const attrId = group.dataset.attrId;
            const attrName = group.dataset.attrName;
            
            // Get values that are CHECKED for this product
            const values = [];
            group.querySelectorAll('.attr-val-checkbox:checked').forEach(vcb => {
                const label = vcb.closest('.attr-val-label');
                values.push({ id: vcb.value, name: label.dataset.valName });
            });
            
            newActiveAttrs.push({ id: attrId, name: attrName, values: values });
        });

        // 2. Update Header
        // Remove existing dynamic headers (they have class 'dynamic-header')
        headerRow.querySelectorAll('.dynamic-header').forEach(el => el.remove());
        
        // Insert new headers before the 'Variant Name' column (which is now index 1+n)
        newActiveAttrs.forEach((attr, i) => {
            const th = document.createElement('th');
            th.className = 'dynamic-header text-xs text-muted font-bold';
            th.style.padding = '0.5rem 0';
            th.textContent = attr.name;
            headerRow.insertBefore(th, headerRow.children[1 + i]);
        });

        // 3. Update Rows
        const rows = tbody.querySelectorAll('.variant-row');
        rows.forEach(row => {
            const rowIndex = row.dataset.index;
            
            // Capture existing selections BEFORE removing elements
            let existingSelections = [];
            
            // From hidden fields (first load)
            row.querySelectorAll('.v-hidden-attr-val').forEach(h => {
                existingSelections.push(h.value);
                h.remove(); // Safe to remove now as we've captured the value
            });
            
            // From already rendered selects (subsequent additions/changes)
            row.querySelectorAll('.dynamic-attr-select').forEach(s => {
                if (s.value) existingSelections.push(s.value);
            });

            // NOW remove existing dynamic cells
            row.querySelectorAll('.dynamic-cell').forEach(el => el.remove());

            newActiveAttrs.forEach((attr, i) => {
                const td = document.createElement('td');
                td.className = 'dynamic-cell';
                td.style.padding = '0.5rem 0';
                td.style.paddingRight = '1rem';
                
                const select = document.createElement('select');
                select.name = `variants[${rowIndex}][attr_values][]`;
                select.className = 'form-control dynamic-attr-select text-sm';
                select.style.minWidth = '120px';
                
                const optNone = document.createElement('option');
                optNone.value = "";
                optNone.textContent = "— Select —";
                select.appendChild(optNone);
                
                attr.values.forEach(val => {
                    const opt = document.createElement('option');
                    opt.value = val.id;
                    opt.textContent = val.name;
                    if (existingSelections.includes(val.id.toString())) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                });
                
                td.appendChild(select);
                row.insertBefore(td, row.children[1 + i]);
            });
        });

        activeVariantAttrs = newActiveAttrs;
    }

    // Initial setup
    updateVariantColumns();

    // Listen for changes in attributes
    document.querySelectorAll('.use-as-variant-checkbox, .attr-val-checkbox').forEach(el => {
        el.addEventListener('change', updateVariantColumns);
    });

    function updateSortOrders() {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach((row, i) => {
            const input = row.querySelector('.sort-order-input');
            if (input) input.value = i;
        });
    }

    btn.addEventListener('click', function() {
        const tr = document.createElement('tr');
        tr.className = 'variant-row';
        tr.dataset.index = index;
        tr.draggable = true;
        
        const isVirtual = document.getElementById('is-virtual-toggle')?.checked;
        const virtualStyle = isVirtual ? '' : 'display: none;';
        
        let html = `<td class="drag-handle" title="Drag to reorder" style="padding: 0.5rem 0;">⋮⋮</td>`;
        
        // Dynamic columns will be added by updateVariantColumns() immediately after this
        
        html += `
            <td style="padding: 0.5rem 0; padding-right: 1rem;">
                <input type="hidden" name="variants[${index}][sort_order]" class="sort-order-input" value="${index}">
                <input type="text" name="variants[${index}][name]" class="form-control" placeholder="e.g. Large" required>
            </td>
            <td style="padding: 0.5rem 0; padding-right: 1rem;"><input type="text" name="variants[${index}][sku]" class="form-control" placeholder="SKU"></td>
            <td style="padding: 0.5rem 0; padding-right: 1rem;"><input type="number" name="variants[${index}][price]" step="0.01" class="form-control" placeholder="Override"></td>
            <td style="padding: 0.5rem 0; padding-right: 1rem;"><input type="number" name="variants[${index}][stock]" class="form-control" value="0"></td>
            <td class="variant-virtual-col" style="padding: 0.5rem 0; padding-right: 1rem; ${virtualStyle}">
                <input type="text" name="variants[${index}][file_path]" class="form-control" placeholder="e.g. storage/downloads/file.zip">
            </td>
            <td class="variant-virtual-col" style="padding: 0.5rem 0; padding-right: 1rem; ${virtualStyle}">
                <select name="variants[${index}][granted_role]" class="form-control">
                    <option value="">— None —</option>
                    <option value="customer">Customer</option>
                    <option value="vip">VIP User</option>
                    <option value="wholesale">Wholesale Client</option>
                </select>
            </td>
            <td style="padding: 0.5rem 0;">
                <button type="button" class="btn btn-outline btn-sm" onclick="this.closest('tr').remove()" style="color: var(--accent); border-color: transparent;">×</button>
            </td>
        `;
        tr.innerHTML = html;
        tbody.appendChild(tr);
        addDragEvents(tr);
        index++;
        updateVariantColumns(); // Re-run to add dynamic cells to new row
        updateSortOrders();
    });

    let dragSrcEl = null;

    function addDragEvents(row) {
        row.addEventListener('dragstart', function(e) {
            dragSrcEl = this;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.innerHTML);
            this.classList.add('dragging');
            e.dataTransfer.setDragImage(this, 20, 20);
        });

        row.addEventListener('dragover', function(e) {
            if (e.preventDefault) e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            if (this !== dragSrcEl) {
                const rect = this.getBoundingClientRect();
                const midpoint = rect.top + rect.height / 2;
                if (e.clientY < midpoint) {
                    this.parentNode.insertBefore(dragSrcEl, this);
                } else {
                    this.parentNode.insertBefore(dragSrcEl, this.nextSibling);
                }
                updateSortOrders();
            }
            return false;
        });

        row.addEventListener('dragend', function() {
            const rows = tbody.querySelectorAll('tr');
            rows.forEach(r => {
                r.classList.remove('dragging');
            });
        });
    }

    document.querySelectorAll('.variant-row').forEach(addDragEvents);

    // ── Virtual Toggle & Group Visibility Logic ──────────────────────────────
    const isVirtualToggle = document.getElementById('is-virtual-toggle');
    const virtualProductSection = document.getElementById('virtual-product-section');
    
    if (isVirtualToggle) {
        isVirtualToggle.addEventListener('change', function() {
            if (virtualProductSection) {
                virtualProductSection.style.display = this.checked ? 'block' : 'none';
            }
            
            const showVirtual = this.checked;
            document.querySelectorAll('.variant-virtual-col').forEach(el => {
                el.style.display = showVirtual ? '' : 'none';
            });
        });
    }

    const virtualTypeSelect = document.getElementById('virtual-type');
    const filePathGroup = document.getElementById('file-path-group');
    const grantedRoleGroup = document.getElementById('granted-role-group');

    if (virtualTypeSelect) {
        virtualTypeSelect.addEventListener('change', function() {
            const val = this.value;
            if (filePathGroup) {
                filePathGroup.style.display = (val === 'file') ? '' : 'none';
            }
            if (grantedRoleGroup) {
                grantedRoleGroup.style.display = (val === 'membership') ? '' : 'none';
            }
        });
    }
});
</script>
