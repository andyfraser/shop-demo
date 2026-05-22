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
    
    <form id="backup-form" action="/admin/backup/download" method="POST">
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
    
    <form id="restore-form" action="/admin/backup/restore" method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>
      
      <div class="form-group">
        <label for="backup_file">Select Backup File</label>
        <input type="file" id="backup_file" name="backup_file" class="form-control" required>
        <small class="form-hint">
          Upload a portable <code>.json</code> backup file. This format supports restoring between different database systems (e.g., MySQL to SQLite).
        </small>
      </div>

      <button type="submit" class="btn btn-danger">
        <span class="ico">📤</span> Restore Database
      </button>
    </form>
  </div>
</div>

<!-- Progress Modal Overlay -->
<div id="progress-modal" class="progress-modal-overlay" style="display: none;">
  <div class="progress-modal-card">
    <div class="progress-modal-header">
      <h3><span class="ico">⚙️</span> <span id="progress-title">Processing...</span></h3>
    </div>
    <div class="progress-modal-body">
      <div class="progress-bar-container">
        <div id="progress-bar-fill" class="progress-bar-fill" style="width: 0%;"></div>
      </div>
      <div class="progress-status-container">
        <span id="progress-percent" class="progress-percent">0%</span>
        <span id="progress-status-msg" class="progress-status-msg">Initializing...</span>
      </div>
    </div>
  </div>
</div>

<style>
.progress-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.progress-modal-overlay.active {
  opacity: 1;
}

.progress-modal-card {
  background: var(--bg-card, #ffffff);
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  width: 90%;
  max-width: 480px;
  padding: 24px;
  border: 1px solid var(--border-color, #e5e7eb);
  transform: translateY(20px);
  transition: transform 0.3s ease;
}

.progress-modal-overlay.active .progress-modal-card {
  transform: translateY(0);
}

.progress-modal-header h3 {
  margin: 0 0 16px 0;
  font-size: 1.15rem;
  font-weight: 600;
  color: var(--text-primary, #111827);
  display: flex;
  align-items: center;
  gap: 8px;
}

.progress-bar-container {
  background: var(--bg-input, #f3f4f6);
  border-radius: 9999px;
  height: 10px;
  width: 100%;
  overflow: hidden;
  position: relative;
  margin-bottom: 12px;
}

.progress-bar-fill {
  background: linear-gradient(90deg, var(--accent, #c8622a), var(--accent-2, #8b3e1c));
  height: 100%;
  width: 0%;
  border-radius: 9999px;
  transition: width 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.progress-bar-fill::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(
    90deg,
    rgba(255, 255, 255, 0) 0%,
    rgba(255, 255, 255, 0.3) 50%,
    rgba(255, 255, 255, 0) 100%
  );
  animation: progress-shimmer 1.5s infinite;
}

@keyframes progress-shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.progress-status-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
}

.progress-percent {
  font-weight: 700;
  color: var(--accent, #c8622a);
  font-variant-numeric: tabular-nums;
}

.progress-status-msg {
  color: var(--text-muted, #6b7280);
  max-width: 75%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Backup handling
  document.getElementById('backup-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const modal = document.getElementById('progress-modal');
    const barFill = document.getElementById('progress-bar-fill');
    const percentText = document.getElementById('progress-percent');
    const statusMsg = document.getElementById('progress-status-msg');
    const title = document.getElementById('progress-title');
    
    title.textContent = 'Generating Database Backup...';
    barFill.style.width = '0%';
    percentText.textContent = '0%';
    statusMsg.textContent = 'Initializing connection...';
    
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('active'), 10);
    
    const eventSource = new EventSource('/admin/backup/stream-download');
    
    eventSource.onmessage = function(event) {
      const data = JSON.parse(event.data);
      
      if (data.error) {
        eventSource.close();
        modal.classList.remove('active');
        setTimeout(() => modal.style.display = 'none', 300);
        alert('Error during backup: ' + data.error);
        return;
      }
      
      if (data.progress !== undefined) {
        barFill.style.width = data.progress + '%';
        percentText.textContent = data.progress + '%';
      }
      if (data.message) {
        statusMsg.textContent = data.message;
      }
      
      if (data.done) {
        eventSource.close();
        statusMsg.textContent = 'Starting download...';
        barFill.style.width = '100%';
        percentText.textContent = '100%';
        
        setTimeout(() => {
          modal.classList.remove('active');
          setTimeout(() => {
            modal.style.display = 'none';
            window.location.href = '/admin/backup/download-file';
          }, 300);
        }, 500);
      }
    };
    
    eventSource.onerror = function() {
      eventSource.close();
      modal.classList.remove('active');
      setTimeout(() => modal.style.display = 'none', 300);
      alert('An error occurred while connecting to the streaming backup service.');
    };
  });

  // Restore handling
  document.getElementById('restore-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('backup_file');
    if (!fileInput.files || fileInput.files.length === 0) {
      alert('Please select a file to restore.');
      return;
    }
    
    if (!confirm('Are you absolutely sure you want to restore the database? This will overwrite everything.')) {
      return;
    }
    
    const file = fileInput.files[0];
    const modal = document.getElementById('progress-modal');
    const barFill = document.getElementById('progress-bar-fill');
    const percentText = document.getElementById('progress-percent');
    const statusMsg = document.getElementById('progress-status-msg');
    const title = document.getElementById('progress-title');
    
    title.textContent = 'Restoring Database...';
    barFill.style.width = '0%';
    percentText.textContent = '0%';
    statusMsg.textContent = 'Uploading backup file...';
    
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('active'), 10);
    
    const formData = new FormData();
    formData.append('backup_file', file);
    
    const csrfTokenEl = document.querySelector('input[name="csrf_token"]');
    const csrfToken = csrfTokenEl ? csrfTokenEl.value : '';
    formData.append('csrf_token', csrfToken);
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/admin/backup/upload-temp', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.upload.onprogress = function(e) {
      if (e.lengthComputable) {
        const uploadPercent = Math.round((e.loaded / e.total) * 100);
        statusMsg.textContent = 'Uploading backup file (' + uploadPercent + '%)...';
        const visualProgress = Math.round((e.loaded / e.total) * 9);
        barFill.style.width = visualProgress + '%';
        percentText.textContent = visualProgress + '%';
      }
    };
    
    xhr.onload = function() {
      if (xhr.status !== 200) {
        let errorMsg = 'Upload failed.';
        try {
          const resp = JSON.parse(xhr.responseText);
          errorMsg = resp.error || errorMsg;
        } catch (err) {}
        
        eventError(errorMsg);
        return;
      }
      
      let resp;
      try {
        resp = JSON.parse(xhr.responseText);
      } catch (err) {
        eventError('Failed to parse upload response.');
        return;
      }
      
      if (!resp.success || !resp.temp_file) {
        eventError(resp.error || 'Failed to save temporary file.');
        return;
      }
      
      statusMsg.textContent = 'Connecting to restore stream...';
      barFill.style.width = '10%';
      percentText.textContent = '10%';
      
      const url = '/admin/backup/stream-restore?temp_file=' + encodeURIComponent(resp.temp_file) + '&filename=' + encodeURIComponent(resp.filename);
      const eventSource = new EventSource(url);
      
      eventSource.onmessage = function(event) {
        const data = JSON.parse(event.data);
        
        if (data.error) {
          eventSource.close();
          eventError(data.error);
          return;
        }
        
        if (data.progress !== undefined) {
          barFill.style.width = data.progress + '%';
          percentText.textContent = data.progress + '%';
        }
        if (data.message) {
          statusMsg.textContent = data.message;
        }
        
        if (data.done) {
          eventSource.close();
          statusMsg.textContent = 'Database restored successfully! Reloading...';
          barFill.style.width = '100%';
          percentText.textContent = '100%';
          
          setTimeout(() => {
            modal.classList.remove('active');
            setTimeout(() => {
              modal.style.display = 'none';
              window.location.reload();
            }, 300);
          }, 1000);
        }
      };
      
      eventSource.onerror = function() {
        eventSource.close();
        eventError('An error occurred during database restoration stream.');
      };
    };
    
    xhr.onerror = function() {
      eventError('Network error occurred during file upload.');
    };
    
    xhr.send(formData);
    
    function eventError(errorMsg) {
      modal.classList.remove('active');
      setTimeout(() => modal.style.display = 'none', 300);
      alert('Restore failed: ' + errorMsg);
    }
  });
});
</script>
