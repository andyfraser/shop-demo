<?php // templates/admin/users_list.php ?>

<div class="admin-topbar">
  <h1>Users</h1>
  <div class="actions">
    <a href="?action=new" class="btn btn-primary">+ Add User</a>
  </div>
</div>

<div class="content">
  <?php if ($flash_msg): ?>
    <div class="alert alert-success"><?= h($flash_msg) ?></div>
  <?php endif; ?>
  <?php if ($flash_err): ?>
    <div class="alert alert-error"><?= h($flash_err) ?></div>
  <?php endif; ?>

  <table class="data-table">
    <thead>
      <tr>
        <th>Name</th><th>Email</th><th>Role</th><th>Orders</th><th>Joined</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><strong><?= h($u['name']) ?></strong></td>
          <td><?= h($u['email']) ?></td>
          <td>
            <span class="badge <?= $u['role'] === 'admin' ? 'badge-danger' : 'badge-neutral' ?>">
              <?= ucfirst($u['role']) ?>
            </span>
          </td>
          <td><?= $u['order_count'] ?></td>
          <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          <td>
            <a href="?action=edit&id=<?= $u['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <?php if ($u['id'] != $current_user['id']): ?>
              <a href="?action=delete&id=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
                 onclick="return confirm('Delete user <?= h(addslashes($u['name'])) ?>?')">Delete</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
