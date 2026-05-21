<?php // templates/admin/delivery_form.php ?>

<?php
  // Helper to handle both object and array during transition or error repopulation
  $get = fn($key) => is_object($option) ? ($option->$key ?? null) : ($option[$key] ?? null);
?>

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

  <div class="card card-sm">
    <form method="POST">
      <?= csrf_field() ?>
      <?php if ($get('id')): ?>
        <input type="hidden" name="id" value="<?= $get('id') ?>">
      <?php endif; ?>

      <div class="form-group">
        <label>Option Name *</label>
        <input type="text" name="name" class="form-control" 
               value="<?= h($get('name') ?? '') ?>" required autofocus placeholder="e.g. Standard Shipping">
      </div>

      <div class="form-group">
        <label>Price *</label>
        <input type="number" name="price" class="form-control" step="0.01" min="0" 
               value="<?= h($get('price') ?? '') ?>" required placeholder="0.00">
      </div>

      <div class="form-group">
        <label>Minimum Order Total</label>
        <input type="number" name="min_order_total" class="form-control" step="0.01" min="0" 
               value="<?= h($get('min_order_total') ?? '0') ?>" placeholder="0.00">
        <small class="form-hint">Option only shows if order subtotal is at least this much. 0 for always.</small>
      </div>

      <div class="form-group">
        <label>Restrict to User Role</label>
        <select name="target_role" class="form-control">
          <option value="">— Available to Everyone —</option>
          <?php foreach ($roles ?? [] as $role): ?>
            <option value="<?= h($role->slug) ?>" <?= ($get('target_role') === $role->slug) ? 'selected' : '' ?>>
              <?= h($role->name) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <small class="form-hint">Restrict this option to users with the selected role (e.g. VIP).</small>
      </div>

      <div class="mt-3">
        <label class="toggle-label">
          <input type="checkbox" name="active" value="1" <?= ($get('active') ?? 1) ? 'checked' : '' ?>>
          <span class="toggle-track"></span>
          Visible in checkout
        </label>
      </div>

      <div class="flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">
          <?= $get('id') ? 'Save Changes' : 'Create Option' ?>
        </button>
        <a href="/admin/delivery" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>
