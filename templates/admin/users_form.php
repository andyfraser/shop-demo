<?php // templates/admin/users_form.php ?>

<div class="admin-topbar">
  <h1><?= $is_new ? 'Add User' : 'Edit: ' . h($user['name'] ?? '') ?></h1>
  <div class="actions">
    <a href="users.php" class="btn btn-outline">← Back</a>
  </div>
</div>

<div class="content">
  <?php if ($errors): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="card" style="max-width:480px;">
    <form method="POST">
        <?= csrf_field() ?>
      <?php if ($user_id): ?>
        <input type="hidden" name="id" value="<?= $user_id ?>">
      <?php endif; ?>

      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" class="form-control"
               value="<?= h($user['name'] ?? '') ?>" required autofocus>
      </div>

      <div class="form-group">
        <label>Email Address *</label>
        <input type="email" name="email" class="form-control"
               value="<?= h($user['email'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label>Role</label>
        <select name="role" class="form-control">
          <option value="customer" <?= ($user['role'] ?? 'customer') === 'customer' ? 'selected' : '' ?>>Customer</option>
          <option value="admin"    <?= ($user['role'] ?? '') === 'admin'             ? 'selected' : '' ?>>Admin</option>
        </select>
      </div>

      <div class="form-group">
        <label>Password <?= $user_id ? '(leave blank to keep current)' : '*' ?></label>
        <input type="password" name="password" class="form-control"
               <?= $is_new ? 'required' : '' ?> minlength="6">
      </div>

      <div style="display:flex;gap:.75rem;margin-top:.5rem;">
        <button type="submit" name="save" class="btn btn-primary">
          <?= $is_new ? 'Create User' : 'Save Changes' ?>
        </button>
        <a href="users.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>
