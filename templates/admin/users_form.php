<?php // templates/admin/users_form.php ?>

<?php
  // Helper to handle both object and array during transition or error repopulation
  $get = fn($key) => is_object($user) ? ($user->$key ?? null) : ($user[$key] ?? null);
?>

<div class="admin-topbar">
  <h1><?= $is_new ? 'Add User' : 'Edit: ' . h($get('name') ?? '') ?></h1>
  <div class="actions">
    <a href="/admin/users" class="btn btn-outline">← Back</a>
  </div>
</div>

<div class="content">
  <?php if ($errors): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="card card-sm" style="max-width:480px;">
    <form method="POST">
        <?= csrf_field() ?>
      <?php if ($user_id): ?>
        <input type="hidden" name="id" value="<?= $user_id ?>">
      <?php endif; ?>

      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" class="form-control"
               value="<?= h($get('name') ?? '') ?>" required autofocus>
      </div>

      <div class="form-group">
        <label>Email Address *</label>
        <input type="email" name="email" class="form-control"
               value="<?= h($get('email') ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label>Role</label>
        <select name="role" class="form-control">
          <?php foreach ($roles as $role): ?>
            <option value="<?= h($role->slug) ?>" <?= ($get('role') ?? 'customer') === $role->slug ? 'selected' : '' ?>>
              <?= h($role->name) ?>
            </option>
          <?php endforeach; ?>
          <option value="admin" <?= ($get('role') ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
      </div>

      <div class="form-group">
        <label>Shipping Address</label>
        <textarea name="address" class="form-control" rows="3"
                  placeholder="Customer's default shipping address"><?= h($get('address') ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label>Password <?= $user_id ? '(leave blank to keep current)' : '*' ?></label>
        <input type="password" name="password" class="form-control"
               <?= $is_new ? 'required' : '' ?> minlength="<?= $password_min_len ?>">
      </div>

      <div class="flex gap-2 mt-2">
        <button type="submit" name="save" class="btn btn-primary">
          <?= $is_new ? 'Create User' : 'Save Changes' ?>
        </button>
        <a href="/admin/users" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>
