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

  <div class="card" style="max-width:600px;margin-bottom:1.5rem;">
    <h3 style="margin:0 0 1rem;font-size:1rem;">Backup Database</h3>
    <p style="font-size:.85rem;color:var(--ink-2);margin:0 0 1.5rem;">
      Download a copy of the current database. This is a complete snapshot of all products, categories, orders, and users.
    </p>
    
    <form action="/admin/backup/download" method="POST">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-primary">
        <span class="ico">📥</span> Download Backup
      </button>
    </form>
  </div>

  <div class="card" style="max-width:600px;margin-bottom:1.5rem;">
    <h3 style="margin:0 0 1rem;font-size:1rem;">Restore Database</h3>
    <p style="font-size:.85rem;color:var(--accent-2);font-weight:600;margin:0 0 1rem;">
      ⚠️ WARNING: Restoring a database will permanently overwrite all current data. This action cannot be undone.
    </p>
    
    <form action="/admin/backup/restore" method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>
      
      <div class="form-group">
        <label for="backup_file">Select Backup File</label>
        <input type="file" id="backup_file" name="backup_file" class="form-control" required>
        <small style="display:block;margin-top:.35rem;font-size:.8rem;color:var(--ink-2);">
          For SQLite, upload a <code>.db</code> file. For MySQL, upload a <code>.sql</code> file.
        </small>
      </div>

      <button type="submit" class="btn btn-danger" onclick="return confirm('Are you absolutely sure you want to restore the database? This will overwrite everything.');">
        <span class="ico">📤</span> Restore Database
      </button>
    </form>
  </div>
</div>
