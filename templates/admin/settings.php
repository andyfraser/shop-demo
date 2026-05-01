<?php // templates/admin/settings.php ?>

<div class="admin-topbar">
  <h1>Settings</h1>
</div>

<div class="content">
  <?php if ($flash_msg): ?>
    <div class="alert alert-success"><?= h($flash_msg) ?></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="/admin/settings">
    <?= csrf_field() ?>

    <div class="card" style="max-width:560px;margin-bottom:1.5rem;">
      <h3 style="margin:0 0 1rem;font-size:1rem;">Store</h3>

      <div class="form-group">
        <label>Site Name</label>
        <input type="text" name="site_name" class="form-control"
               value="<?= h($settings->site_name) ?>" required>
        <small style="color:var(--ink-2);">Use <code>|</code> to split the logo into two parts, e.g. <code>Demo|shop</code> — the second part is styled differently in the header.</small>
      </div>

      <div class="form-group">
        <label>Currency Symbol</label>
        <input type="text" name="currency_symbol" class="form-control"
               value="<?= h($settings->currency_symbol) ?>" required style="max-width:6rem;">
      </div>

      <div class="form-group">
        <label>Email From Address</label>
        <input type="email" name="email_from" class="form-control"
               value="<?= h($settings->email_from) ?>" required>
        <small style="color:var(--ink-2);">The email address used for all outgoing system emails.</small>
      </div>

      <div class="form-group">
        <label>Site URL (for CLI links)</label>
        <input type="url" name="site_url" class="form-control"
               value="<?= h($settings->site_url) ?>" required>
        <small style="color:var(--ink-2);">The full domain (e.g. <code>http://shop-demo.test</code>) used for links in emails sent from the CLI.</small>
      </div>

      <div class="form-group">
        <label>Base URL (subdirectory)</label>
        <input type="text" name="base_url" class="form-control"
               value="<?= h($settings->base_url) ?>">
        <small style="color:var(--ink-2);">If the shop is in a subdirectory, include it here (e.g. <code>/shop</code>). Leave empty if running at the root.</small>
      </div>

      <div class="form-group">
        <label>Low Stock Threshold</label>
        <input type="number" name="low_stock_threshold" class="form-control"
               value="<?= h($settings->low_stock_threshold) ?>" min="0" required style="max-width:6rem;">
        <small style="color:var(--ink-2);">Products with stock at or below this level will trigger low stock warnings.</small>
      </div>

      <div class="form-group">
        <label>Default VAT Rate (%)</label>
        <input type="number" name="default_vat_rate" class="form-control"
               value="<?= h($settings->default_vat_rate) ?>" min="0" step="0.01" required style="max-width:6rem;">
        <small style="color:var(--ink-2);">The default VAT percentage for new products and delivery costs.</small>
      </div>
    </div>

    <div class="card" style="max-width:560px;margin-bottom:1.5rem;">
      <h3 style="margin:0 0 1rem;font-size:1rem;">Security</h3>

      <div class="form-group">
        <label>Minimum Password Length</label>
        <input type="number" name="password_min_length" class="form-control"
               value="<?= h($settings->password_min_length) ?>" min="1" required style="max-width:8rem;">
      </div>

      <div class="form-group">
        <label>Remember Me — Duration (days)</label>
        <input type="number" name="remember_me_days" class="form-control"
               value="<?= h($settings->remember_me_days) ?>" min="1" required style="max-width:8rem;">
        <small style="color:var(--ink-2);">How long a "Remember me" login persists before the user must sign in again.</small>
      </div>
    </div>

    <div class="card" style="max-width:560px;margin-bottom:1.5rem;">
      <h3 style="margin:0 0 1rem;font-size:1rem;">Rate Limiting</h3>

      <p style="font-size:.85rem;color:var(--ink-2);margin:0 0 1rem;">
        Limits apply per IP address. Accounts are temporarily blocked after the specified number of failed attempts within the time window.
      </p>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group">
          <label>Login — Max Attempts</label>
          <input type="number" name="login_max_attempts" class="form-control"
                 value="<?= h($settings->login_max_attempts) ?>" min="1" required>
        </div>
        <div class="form-group">
          <label>Login — Window (minutes)</label>
          <input type="number" name="login_window_minutes" class="form-control"
                 value="<?= h($settings->login_window_minutes) ?>" min="1" required>
        </div>
        <div class="form-group">
          <label>Registration — Max Attempts</label>
          <input type="number" name="register_max_attempts" class="form-control"
                 value="<?= h($settings->register_max_attempts) ?>" min="1" required>
        </div>
        <div class="form-group">
          <label>Registration — Window (minutes)</label>
          <input type="number" name="register_window_minutes" class="form-control"
                 value="<?= h($settings->register_window_minutes) ?>" min="1" required>
        </div>
      </div>
    </div>

    <div class="card" style="max-width:560px;margin-bottom:1.5rem;">
      <h3 style="margin:0 0 1rem;font-size:1rem;">Mobile Navigation</h3>

      <p style="font-size:.85rem;color:var(--ink-2);margin:0 0 1rem;">
        Controls whether sub-categories are expanded by default in the mobile hamburger menu.
        If either threshold is exceeded, only top-level categories are shown.
      </p>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group">
          <label>Max Top-Level Categories</label>
          <input type="number" name="mobile_nav_max_top" class="form-control"
                 value="<?= h($settings->mobile_nav_max_top) ?>" min="1" required>
          <small style="color:var(--ink-2);">Default: 10</small>
        </div>
        <div class="form-group">
          <label>Max Combined Categories</label>
          <input type="number" name="mobile_nav_max_combined" class="form-control"
                 value="<?= h($settings->mobile_nav_max_combined) ?>" min="1" required>
          <small style="color:var(--ink-2);">Top-level + first-level total. Default: 20</small>
        </div>
      </div>
    </div>

    <button type="submit" class="btn btn-primary">Save Settings</button>
  </form>
</div>
