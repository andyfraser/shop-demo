<?php // templates/admin/delivery_form.php ?>
<div class="admin-topbar">
  <h1><?= h($page_title) ?></h1>
  <div class="actions">
    <a href="/admin/delivery" class="btn btn-outline">← Back</a>
  </div>
</div>

<div class="content">
  <?php if ($errors): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="card" style="max-width:500px;">
    <form method="POST">
      <?= csrf_field() ?>
      <?php if (isset($option['id']) && $option['id']): ?>
        <input type="hidden" name="id" value="<?= $option['id'] ?>">
      <?php endif; ?>

      <div class="form-group">
        <label>Option Name *</label>
        <input type="text" name="name" class="form-control" 
               value="<?= h($option['name'] ?? '') ?>" required autofocus placeholder="e.g. Standard Shipping">
      </div>

      <div class="form-group">
        <label>Price *</label>
        <input type="number" name="price" class="form-control" step="0.01" min="0" 
               value="<?= h($option['price'] ?? '') ?>" required placeholder="0.00">
      </div>

      <div class="form-group">
        <label>Minimum Order Total</label>
        <input type="number" name="min_order_total" class="form-control" step="0.01" min="0" 
               value="<?= h($option['min_order_total'] ?? '0') ?>" placeholder="0.00">
        <small style="color:var(--ink-2)">Option only shows if order subtotal is at least this much. 0 for always.</small>
      </div>

      <div style="margin-top:1.5rem;">
        <label class="toggle-label">
          <input type="checkbox" name="active" value="1" <?= ($option['active'] ?? 1) ? 'checked' : '' ?>>
          <span class="toggle-track"></span>
          Visible in checkout
        </label>
      </div>

      <div style="display:flex;gap:.75rem;margin-top:2rem;">
        <button type="submit" class="btn btn-primary">
          <?= (isset($option['id']) && $option['id']) ? 'Save Changes' : 'Create Option' ?>
        </button>
        <a href="/admin/delivery" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>
