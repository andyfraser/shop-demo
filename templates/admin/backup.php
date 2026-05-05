<?php // templates/admin/backup.php
// Expects: $page_title, $active, $flash_msg, $error_msg
?>

<div class="admin-topbar">
  <h1><?= h($page_title) ?></h1>
</div>

<div class="content">
  <?php if ($flash_msg): ?>
    <div class="alert alert-success"><?= h($flash_msg) ?></div>
  <?php endif; ?>

  <?php if ($error_msg): ?>
    <div class="alert alert-error"><?= h($error_msg) ?></div>
  <?php endif; ?>

  <div class="card card-md mb-3">
    <h3 class="mb-2 text-sm" style="margin-top:0;">Backup Database</h3>
    <p class="text-sm text-muted mb-3">
      Download a copy of the current database. This is a complete snapshot of all products, categories, orders, and users.
    </p>
    
    <form action="/admin/backup/download" method="POST">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-primary">
        <span class="ico">📥</span> Download Backup
      </button>
    </form>
  </div>

  <div class="card card-md mb-3">
    <h3 class="mb-2 text-sm" style="margin-top:0;">Restore Database</h3>
    <p class="text-sm mb-2" style="color:var(--accent-2);font-weight:600;">
      ⚠️ WARNING: Restoring a database will permanently overwrite all current data. This action cannot be undone.
    </p>
    
    <form action="/admin/backup/restore" method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>
      
      <div class="form-group">
        <label for="backup_file">Select Backup File</label>
        <input type="file" id="backup_file" name="backup_file" class="form-control" required>
        <small class="form-hint">
          Upload a portable <code>.json</code> backup file. This format supports restoring between different database systems (e.g., MySQL to SQLite).
        </small>
      </div>

      <button type="submit" class="btn btn-danger" onclick="return confirm('Are you absolutely sure you want to restore the database? This will overwrite everything.');">
        <span class="ico">📤</span> Restore Database
      </button>
    </form>
  </div>
</div>
