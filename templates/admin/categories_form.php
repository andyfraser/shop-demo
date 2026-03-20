<?php // templates/admin/categories_form.php ?>

<div class="admin-topbar">
  <h1><?= $is_new ? 'Add Category' : 'Edit Category' ?></h1>
  <div class="actions">
    <a href="categories.php" class="btn btn-outline">← Back</a>
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
      <?php if ($category_id): ?>
        <input type="hidden" name="id" value="<?= $category_id ?>">
      <?php endif; ?>

      <div class="form-group">
        <label>Name *</label>
        <input type="text" name="name" class="form-control"
               value="<?= h($category['name'] ?? '') ?>" required autofocus>
      </div>

      <div class="form-group">
        <label>Parent Category</label>
        <select name="parent_id" class="form-control">
          <option value="">— Top level —</option>
          <?php foreach ($all_categories as $c): ?>
            <?php if ($c['id'] == $category_id) continue; ?>
            <option value="<?= $c['id'] ?>"
                    <?= ($category['parent_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
              <?= h($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Icon <small style="color:var(--ink-2);font-weight:400;">— paste any emoji</small></label>
        <input type="text" name="icon" class="form-control"
               value="<?= h($category['icon'] ?? '') ?>"
               placeholder="e.g. 💻" style="max-width:120px;">
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="form-control"><?= h($category['description'] ?? '') ?></textarea>
      </div>

      <div style="display:flex;gap:.75rem;">
        <button type="submit" name="save" class="btn btn-primary">
          <?= $is_new ? 'Create Category' : 'Save Changes' ?>
        </button>
        <a href="categories.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>
