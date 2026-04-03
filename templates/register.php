<?php // templates/register.php ?>
<div class="container">
  <div class="auth-wrap">
    <h1 class="auth-title">Create account</h1>
    <p class="auth-sub">Join Demoshop to start shopping</p>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <form method="POST">
        <?= csrf_field() ?>
        <div class="form-group">
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name" class="form-control"
                 value="<?= h($name ?? '') ?>" required autofocus>
        </div>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" class="form-control"
                 value="<?= h($email ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control"
                 required minlength="6">
        </div>
        <div class="form-group">
          <label for="password2">Confirm Password</label>
          <input type="password" id="password2" name="password2" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary"
                style="width:100%;justify-content:center;padding:.7rem;">
          Create Account
        </button>
      </form>
    </div>
    <p style="text-align:center;margin-top:1rem;font-size:.875rem;color:var(--ink-2);">
      Already have an account?
      <a href="login.php" style="color:var(--accent);">Sign in</a>
    </p>
  </div>
</div>
