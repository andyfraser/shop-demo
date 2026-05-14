<?php // templates/admin/user_roles_list.php ?>

<div class="admin-topbar">
  <h1><?= h($page_title) ?></h1>
  <div class="actions">
    <a href="/admin/user-roles/create" class="btn btn-primary">+ Add Role</a>
    <a href="/admin/users" class="btn btn-outline">Back to Users</a>
  </div>
</div>

<div class="content">
  <?php if (isset($flash_msg)): ?><div class="alert alert-success"><?= h($flash_msg) ?></div><?php endif; ?>

  <table class="data-table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Slug</th>
        <th>Description</th>
        <th class="text-right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($roles as $role): ?>
        <tr>
          <td><strong><?= h($role->name) ?></strong></td>
          <td>
            <code style="font-size:.78rem;background:var(--sand);padding:.1rem .4rem;border-radius:2px;">
              <?= h($role->slug) ?>
            </code>
          </td>
          <td><?= h($role->description) ?></td>
          <td class="text-right">
            <a href="/admin/user-roles/edit?id=<?= $role->id ?>" class="btn btn-outline btn-sm">Edit</a>
            <?php if (!in_array($role->slug, ['admin', 'customer'])): ?>
              <a href="/admin/user-roles/delete?id=<?= $role->id ?>" class="btn btn-danger btn-sm" 
                 onclick="return confirm('Are you sure?')">Delete</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
