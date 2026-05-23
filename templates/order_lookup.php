<?php // templates/order_lookup.php ?>
<div class="container">
  <div class="auth-wrap" style="max-width: 460px;">
    <h1 class="auth-title" style="text-align: center; margin-bottom: 0.5rem; font-family: var(--font-display);">Track Your Order</h1>
    <p class="auth-sub" style="text-align: center; color: var(--ink-2); margin-bottom: 2rem;">Enter your order details to view your receipt, shipment updates, and order status.</p>

    <?php if (!empty($errors['general'])): ?>
      <div class="alert alert-danger" style="margin-bottom: 1.5rem; border-radius: var(--radius); padding: 0.75rem 1rem;">
        <?= h($errors['general']) ?>
      </div>
    <?php endif; ?>

    <div class="card" style="padding: 2rem; border-radius: var(--radius); border: 1px solid var(--line); background: var(--bg-card); box-shadow: 0 4px 20px rgba(0,0,0,0.05); transition: transform 0.2s ease;">
      <form method="POST" action="/order/lookup">
        <?= csrf_field() ?>

        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label for="order_id" style="font-weight: 500; margin-bottom: 0.5rem; display: block; font-size: 0.875rem;">Order ID</label>
          <input type="text" id="order_id" name="order_id" class="form-control" 
                 placeholder="e.g. 000042 or 42" 
                 value="<?= h($order_id ?? '') ?>" 
                 style="width: 100%; padding: 0.75rem; border-radius: var(--radius); border: 1px solid <?= !empty($errors['order_id']) ? 'var(--danger-ink)' : 'var(--line)' ?>; background: var(--bg-1);"
                 required autofocus>
          <?php if (!empty($errors['order_id'])): ?>
            <div style="color: var(--danger-ink); font-size: 0.75rem; margin-top: 0.25rem;"><?= h($errors['order_id']) ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group" style="margin-bottom: 1.75rem;">
          <label for="email" style="font-weight: 500; margin-bottom: 0.5rem; display: block; font-size: 0.875rem;">Email Address</label>
          <input type="email" id="email" name="email" class="form-control" 
                 placeholder="e.g. guest@example.com" 
                 value="<?= h($email ?? '') ?>" 
                 style="width: 100%; padding: 0.75rem; border-radius: var(--radius); border: 1px solid <?= !empty($errors['email']) ? 'var(--danger-ink)' : 'var(--line)' ?>; background: var(--bg-1);"
                 required>
          <?php if (!empty($errors['email'])): ?>
            <div style="color: var(--danger-ink); font-size: 0.75rem; margin-top: 0.25rem;"><?= h($errors['email']) ?></div>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary" 
                style="width: 100%; padding: 0.75rem; justify-content: center; font-weight: 600; border-radius: var(--radius); display: flex; align-items: center; gap: 0.5rem; transition: background 0.2s ease, transform 0.1s ease;">
          <span>🔍</span> Track Order
        </button>
      </form>
    </div>

    <p style="text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: var(--ink-2);">
      Registered user? 
      <a href="/login" style="color: var(--accent); font-weight: 500; text-decoration: none;">Sign in here</a> to see your order history.
    </p>
  </div>
</div>
