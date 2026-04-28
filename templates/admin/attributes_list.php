<?php // templates/admin/attributes_list.php ?>

<div class="admin-topbar">
  <h1>Attributes</h1>
  <div class="actions">
    <a href="/admin/attributes/new" class="btn btn-primary">+ Add Attribute</a>
  </div>
</div>

<div class="content">
  <?php if ($flash_msg): ?>
    <div class="alert alert-success"><?= h($flash_msg) ?></div>
  <?php endif; ?>

  <table class="data-table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Values</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($attributes as $attr): ?>
        <tr>
          <td><strong><?= h($attr['name']) ?></strong></td>
          <td>
            <?php 
              // We don't have a count in the basic getAll, but we could add it.
              // For now, let's just show the name.
              // Actually, most other lists show some details.
            ?>
            <span style="color:var(--ink-2);font-size:.8rem;">Managed globally</span>
          </td>
          <td>
            <a href="/admin/attributes/edit?id=<?= $attr['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <a href="/admin/attributes/delete?id=<?= $attr['id'] ?>" class="btn btn-danger btn-sm"
               onclick="return confirm('Delete this attribute and all its values?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($attributes)): ?>
        <tr>
          <td colspan="3" class="text-center">No attributes found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
