<?php // templates/admin/user_roles_form.php ?>

<div class="admin-topbar">
  <h1><?= h($page_title) ?></h1>
  <div class="actions">
    <a href="/admin/user-roles" class="btn btn-outline">← Back</a>
  </div>
</div>

<div class="content">
  <?php if ($errors): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php
    $get = fn($key) => isset($role) && $role ? (is_object($role) ? ($role->$key ?? null) : ($role[$key] ?? null)) : null;
  ?>

  <div class="card card-sm" style="max-width:480px;">
    <form method="POST">
      <?= csrf_field() ?>
      <?php if (!$is_new): ?>
        <input type="hidden" name="id" value="<?= $role_id ?>">
      <?php endif; ?>

      <div class="form-group">
        <label>Role Name *</label>
        <input type="text" name="name" class="form-control" value="<?= h($get('name') ?? '') ?>" required autofocus>
        <div class="form-hint">Display name for the role (e.g. VIP Customer).</div>
      </div>

      <div class="form-group">
        <label>Role Slug *</label>
        <input type="text" name="slug" class="form-control" value="<?= h($get('slug') ?? '') ?>" required 
               <?= in_array($get('slug'), ['admin', 'customer']) ? 'readonly' : '' ?>>
        <div class="form-hint">Unique identifier used in the database (e.g. vip).</div>
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="form-control" rows="3"><?= h($get('description') ?? '') ?></textarea>
      </div>

      <div class="flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">
          <?= $is_new ? 'Create Role' : 'Save Changes' ?>
        </button>
        <a href="/admin/user-roles" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>
