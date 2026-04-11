<?php // templates/login.php ?>
<div class="container">
  <div class="auth-wrap">
    <h1 class="auth-title">Welcome back</h1>
    <p class="auth-sub">Sign in to your Demoshop account</p>

    <?php if ($errors): ?>
      <div class="alert alert-error"><?= h($errors[0]) ?></div>
    <?php endif; ?>

    <div class="alert alert-info" style="font-size:.8rem;">
      <strong>Demo accounts:</strong><br>
      Admin: admin@shop.local / password<br>
      Customer: jane@example.com / password
    </div>

    <div class="card">
      <form method="POST">
        <?= csrf_field() ?>
        <div class="form-group">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" class="form-control"
                 value="<?= h($email ?? '') ?>" required autofocus>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <label class="toggle-row">
          <input type="checkbox" name="remember_me" value="1"
                 <?= !empty($_POST['remember_me']) ? 'checked' : '' ?>>
          <span class="toggle-track"></span>
          <span class="toggle-label">Remember me for <?= h(setting('remember_me_days')) ?> days</span>
        </label>
        <button type="submit" class="btn btn-primary"
                style="width:100%;justify-content:center;padding:.7rem;">
          Sign In
        </button>
      </form>
    </div>
    <p style="text-align:center;margin-top:1rem;font-size:.875rem;color:var(--ink-2);">
      Don't have an account?
      <a href="/register" style="color:var(--accent);">Register here</a>
    </p>
  </div>
</div>
