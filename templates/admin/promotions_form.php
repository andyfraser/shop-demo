<?php // templates/admin/promotions_form.php ?>

<div class="admin-topbar">
  <h1><?= h($page_title) ?></h1>
  <div class="actions">
    <a href="/admin/promotions" class="btn btn-outline">← Back</a>
  </div>
</div>

<div class="content">
  <?php if ($errors): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php
    $get = fn($key) => isset($promotion) && $promotion ? (is_object($promotion) ? ($promotion->$key ?? null) : ($promotion[$key] ?? null)) : null;
    $target_ids = $get('target_ids') ?? [];
  ?>

  <div class="card card-lg" style="max-width:1100px;">
    <form method="POST">
      <?= csrf_field() ?>
      <?php if (!$is_new): ?>
        <input type="hidden" name="id" value="<?= $promotion_id ?>">
      <?php endif; ?>

      <div class="form-grid">
        <div class="span-2" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
          <div>
            <div class="form-group">
              <label>Promotion Name *</label>
              <input type="text" name="name" class="form-control"
                value="<?= h($get('name') ?? '') ?>" required>
              <div class="form-hint">Internal name for reference.</div>
            </div>
            <div class="form-group">
              <label>Customer Description</label>
              <textarea name="description" class="form-control" rows="2"><?= h($get('description') ?? '') ?></textarea>
              <div class="form-hint">Shown in the cart when applied.</div>
            </div>
          </div>

          <div>
            <div class="form-group">
              <label>Promo Code</label>
              <input type="text" name="code" class="form-control"
                value="<?= h($get('code') ?? '') ?>" placeholder="e.g. SAVE10">
              <div class="form-hint">Leave blank for automatic application.</div>
            </div>

            <div class="bg-sand p-3 mb-3" style="border-radius:var(--radius);">
              <label class="toggle-label">
                <input type="checkbox" name="active" value="1" <?= ($get('active') ?? 1) ? 'checked' : '' ?>>
                <span class="toggle-track"></span>
                Active
              </label>
            </div>
          </div>
        </div>

        <div class="span-2 mt-2">
          <h3 class="section-title border-bottom">Discount Rules</h3>
          <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr;">
            <div class="form-group">
              <label>Discount Type *</label>
              <select id="type" name="type" class="form-control" required onchange="toggleBogoFields()">
                <option value="percentage" <?= ($get('type') ?? '') === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                <option value="fixed_amount" <?= ($get('type') ?? '') === 'fixed_amount' ? 'selected' : '' ?>>Fixed Amount</option>
                <option value="free_shipping" <?= ($get('type') ?? '') === 'free_shipping' ? 'selected' : '' ?>>Free Shipping</option>
                <option value="buy_x_get_y" <?= ($get('type') ?? '') === 'buy_x_get_y' ? 'selected' : '' ?>>Buy X Get Y</option>
              </select>
            </div>
            <div class="form-group">
              <label id="value-label">Value *</label>
              <input type="number" name="value" class="form-control" step="0.01" value="<?= h($get('value') ?? 0) ?>" required>
              <div id="value-hint" class="form-hint">Percentage or fixed amount.</div>
            </div>
            <div class="form-group">
              <label>Min Order Subtotal</label>
              <input type="number" name="min_order_amount" class="form-control" step="0.01" value="<?= h($get('min_order_amount') ?? 0) ?>">
            </div>
          </div>

          <div id="bogo-fields" class="form-grid mt-2" style="grid-template-columns: 1fr 1fr; display:none;">
            <div class="form-group">
              <label>Buy Quantity (X) *</label>
              <input type="number" name="buy_qty" class="form-control" value="<?= h($get('buy_qty') ?? '') ?>">
              <div class="form-hint">Number of items to buy.</div>
            </div>
            <div class="form-group">
              <label>Get Quantity (Y) *</label>
              <input type="number" name="get_qty" class="form-control" value="<?= h($get('get_qty') ?? '') ?>">
              <div class="form-hint">Number of items discounted.</div>
            </div>
          </div>
        </div>

        <div class="span-2 mt-2">
          <h3 class="section-title border-bottom">Scope & Targeting</h3>
          <div class="form-group">
            <label>Apply To *</label>
            <select id="target_type" name="target_type" class="form-control" required onchange="toggleTargets()">
              <option value="order" <?= ($get('target_type') ?? '') === 'order' ? 'selected' : '' ?>>Entire Order</option>
              <option value="product" <?= ($get('target_type') ?? '') === 'product' ? 'selected' : '' ?>>Specific Products</option>
              <option value="category" <?= ($get('target_type') ?? '') === 'category' ? 'selected' : '' ?>>Specific Categories</option>
            </select>
          </div>

          <div id="target-selector" class="form-group" style="display:none;">
            <div class="flex flex-center gap-3 mb-2">
              <label id="target-label" class="mb-0">Select Targets</label>
              <div class="attr-search-container" style="width:200px;">
                <span class="attr-search-icon">🔍</span>
                <input type="text" id="target-search" class="form-control text-xs" placeholder="Search..." style="padding: 0.3rem 0.5rem 0.3rem 1.8rem;">
              </div>
            </div>
            <div class="bg-sand p-3" style="max-height: 250px; overflow-y: auto; border: 1px solid var(--line); border-radius: var(--radius);">
              <div id="product-targets" style="display:none;">
                <?php foreach ($products as $p): ?>
                  <label class="flex gap-2 mb-1 target-item" style="font-weight:400;cursor:pointer;">
                    <input type="checkbox" name="target_ids[]" value="<?= $p->id ?>" <?= in_array($p->id, $target_ids) ? 'checked' : '' ?>>
                    <span class="target-name"><?= h($p->name) ?></span> 
                    <span class="text-xs text-muted">(<?= money($p->price) ?>, <?= h($p->cat_name ?? 'No Category') ?>)</span>
                  </label>
                <?php endforeach; ?>
              </div>
              <div id="category-targets" style="display:none;">
                <?php foreach ($categories as $c): ?>
                  <label class="flex gap-2 mb-1 target-item" style="font-weight:400;cursor:pointer;">
                    <input type="checkbox" name="target_ids[]" value="<?= $c->id ?>" <?= in_array($c->id, $target_ids) ? 'checked' : '' ?>>
                    <span class="target-name"><?= $c->parent_name ? h($c->parent_name) . ' › ' : '' ?><?= h($c->name) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="span-2 mt-2">
          <h3 class="section-title border-bottom">Limits & Schedule</h3>
          <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr;">
            <div class="form-group">
              <label>Start Date</label>
              <input type="datetime-local" name="start_date" class="form-control" 
                value="<?= $get('start_date') ? date('Y-m-d\TH:i', strtotime($get('start_date'))) : '' ?>">
            </div>
            <div class="form-group">
              <label>End Date</label>
              <input type="datetime-local" name="end_date" class="form-control" 
                value="<?= $get('end_date') ? date('Y-m-d\TH:i', strtotime($get('end_date'))) : '' ?>">
            </div>
            <div class="form-group">
              <label>Usage Limit</label>
              <input type="number" name="usage_limit" class="form-control" value="<?= h($get('usage_limit') ?? '') ?>">
              <div class="form-hint">Max overall uses.</div>
            </div>
          </div>
          <?php if (!$is_new): ?>
            <div class="mt-2 text-sm">
              <strong>Current Usage:</strong> <span class="text-accent-2 font-bold"><?= $get('used_count') ?></span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="flex gap-2 mt-4 border-top pt-3">
        <button type="submit" class="btn btn-primary">
          <?= $is_new ? 'Create Promotion' : 'Save Changes' ?>
        </button>
        <a href="/admin/promotions" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
function toggleBogoFields() {
    const type = document.getElementById('type').value;
    const bogoFields = document.getElementById('bogo-fields');
    const valueLabel = document.getElementById('value-label');
    const valueHint = document.getElementById('value-hint');

    if (type === 'buy_x_get_y') {
        bogoFields.style.display = 'grid';
        valueLabel.innerText = 'Discount % on Y *';
        valueHint.innerText = 'e.g. 100 for free, 50 for half price.';
        document.getElementsByName('buy_qty')[0].required = true;
        document.getElementsByName('get_qty')[0].required = true;
    } else {
        bogoFields.style.display = 'none';
        valueLabel.innerText = 'Value *';
        valueHint.innerText = type === 'percentage' ? 'Percentage (0-100).' : 'Fixed amount in currency.';
        document.getElementsByName('buy_qty')[0].required = false;
        document.getElementsByName('get_qty')[0].required = false;
    }
}

function toggleTargets() {
    const targetType = document.getElementById('target_type').value;
    const selector = document.getElementById('target-selector');
    const productTargets = document.getElementById('product-targets');
    const categoryTargets = document.getElementById('category-targets');
    const label = document.getElementById('target-label');
    const search = document.getElementById('target-search');

    if (search) search.value = ''; // Reset search when switching
    filterTargets('');

    if (targetType === 'order') {
        selector.style.display = 'none';
    } else if (targetType === 'product') {
        selector.style.display = 'block';
        productTargets.style.display = 'block';
        categoryTargets.style.display = 'none';
        label.innerText = 'Select Products';
    } else if (targetType === 'category') {
        selector.style.display = 'block';
        productTargets.style.display = 'none';
        categoryTargets.style.display = 'block';
        label.innerText = 'Select Categories';
    }
}

function filterTargets(term) {
    term = term.toLowerCase();
    const items = document.querySelectorAll('.target-item');
    items.forEach(item => {
        const name = item.querySelector('.target-name').textContent.toLowerCase();
        item.style.display = name.includes(term) ? 'flex' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    toggleTargets();
    toggleBogoFields();
    
    const search = document.getElementById('target-search');
    if (search) {
        search.addEventListener('input', (e) => filterTargets(e.target.value));
    }
});
</script>
