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

  <div class="card" style="max-width:1100px;">
    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
      <?php if ($product_id): ?>
        <input type="hidden" name="id" value="<?= $product_id ?>">
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

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
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
            <div style="display: flex; gap: 1.5rem; align-items: flex-start;">
                <?php if ($img_url): ?>
                <div style="flex-shrink: 0;">
                    <img src="<?= $img_url ?>" alt=""
                        style="height:140px;width:140px;object-fit:cover;border-radius:var(--radius);display:block;border:1px solid var(--line);">
                    <label style="display:flex;align-items:center;gap:.4rem;margin-top:.5rem;font-weight:400;cursor:pointer;font-size:0.8rem;">
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
                    <div style="font-size:.75rem;color:var(--ink-2);margin-top:.35rem;">
                    JPEG, PNG, GIF or WebP — max 5MB
                    </div>
                </div>
            </div>
          </div>
        </div>

        <div class="span-2" style="display:flex;gap:2rem;padding:1rem;background:var(--sand);border-radius:var(--radius);margin-bottom:1rem;">
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
            Featured Product
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
            Product Attributes (for filtering)
          </h3>
          <p style="font-size: 0.85rem; color: var(--ink-2); margin-bottom: 1rem;">
            Select all applicable attributes for this product. These are used for faceted filtering on the storefront.
            Mark an attribute as <strong>"Use as Variant"</strong> to enable specific dropdowns in the variants table below.
          </p>
          
          <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.5rem;">
            <?php 
              $variant_attr_ids = is_object($product) ? ($product->variant_attribute_ids ?? []) : ($_POST['variant_attribute_ids'] ?? []);
            ?>
            <?php foreach ($all_attributes as $attr): ?>
              <div class="attr-group" data-attr-id="<?= $attr['id'] ?>" data-attr-name="<?= h($attr['name']) ?>">
                <strong style="display: block; font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--ink);"><?= h($attr['name']) ?></strong>
                <div style="display: flex; flex-direction: column; gap: 0.4rem; max-height: 150px; overflow-y: auto; padding: 0.5rem; border: 1px solid var(--line); border-radius: var(--radius); background: #fdfdfd;">
                  <?php foreach ($attr['values'] as $val): ?>
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer; font-weight: 400;" class="attr-val-label" data-val-id="<?= $val['id'] ?>" data-val-name="<?= h($val['value']) ?>">
                      <input type="checkbox" name="attribute_value_ids[]" value="<?= $val['id'] ?>" class="attr-val-checkbox"
                        <?= in_array($val['id'], $product_attribute_ids) ? 'checked' : '' ?>>
                      <?= h($val['value']) ?>
                    </label>
                  <?php endforeach; ?>
                </div>
                <div style="margin-top: 0.5rem;">
                    <label class="toggle-label" style="font-size: 0.75rem;">
                        <input type="checkbox" name="variant_attribute_ids[]" value="<?= $attr['id'] ?>" class="use-as-variant-checkbox"
                            <?= in_array($attr['id'], $variant_attr_ids) ? 'checked' : '' ?>>
                        <span class="toggle-track"></span>
                        Use as Variant
                    </label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="span-2" style="margin-top: 1rem;">
          <h3 style="font-family: var(--font-display); font-size: 1.2rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--line); padding-bottom: 0.5rem;">
            Product Variants
          </h3>
          <p style="font-size: 0.85rem; color: var(--ink-2); margin-bottom: 1rem;">
            Add variations like size or color. If using variant attributes, select them from the dropdowns. 
            Variant Name is optional if attributes are selected.
          </p>

          <div style="overflow-x: auto;">
            <table class="table" id="variants-table" style="width: 100%; min-width: 800px;">
              <thead>
                <tr id="variant-header-row">
                  <th style="width: 40px;"></th>
                  <!-- Dynamic columns will be inserted here -->
                  <th>Variant Name / Label</th>
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
                  <tr draggable="true" class="variant-row" data-index="<?= $index ?>">
                    <td class="drag-handle" title="Drag to reorder">⋮⋮</td>
                    <!-- Dynamic values will be inserted here by JS -->
                    <td>
                      <input type="hidden" name="variants[<?= $index ?>][id]" value="<?= $v->id ?>">
                      <input type="hidden" name="variants[<?= $index ?>][sort_order]" class="sort-order-input" value="<?= $v->sort_order ?>">
                      <input type="text" name="variants[<?= $index ?>][name]" class="form-control" value="<?= h($v->name) ?>" placeholder="e.g. Blue Small">
                    </td>
                    <td><input type="text" name="variants[<?= $index ?>][sku]" class="form-control" value="<?= h($v->sku ?? '') ?>" placeholder="SKU"></td>
                    <td><input type="number" name="variants[<?= $index ?>][price]" step="0.01" class="form-control" value="<?= h($v->price ?? '') ?>" placeholder="<?= h($get('price')) ?>"></td>
                    <td><input type="number" name="variants[<?= $index ?>][stock]" class="form-control" value="<?= h($v->stock) ?>"></td>
                    <td>
                      <label style="color: var(--accent); cursor: pointer; font-size: 0.8rem;">
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
            th.className = 'dynamic-header';
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
                
                const select = document.createElement('select');
                select.name = `variants[${rowIndex}][attr_values][]`;
                select.className = 'form-control dynamic-attr-select';
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
        
        let html = `<td class="drag-handle" title="Drag to reorder">⋮⋮</td>`;
        
        // Dynamic columns will be added by updateVariantColumns() immediately after this
        
        html += `
            <td>
                <input type="hidden" name="variants[${index}][sort_order]" class="sort-order-input" value="${index}">
                <input type="text" name="variants[${index}][name]" class="form-control" placeholder="e.g. Large">
            </td>
            <td><input type="text" name="variants[${index}][sku]" class="form-control" placeholder="SKU"></td>
            <td><input type="number" name="variants[${index}][price]" step="0.01" class="form-control" placeholder="Override"></td>
            <td><input type="number" name="variants[${index}][stock]" class="form-control" value="0"></td>
            <td></td>
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
});
</script>
