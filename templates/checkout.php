<?php // templates/checkout.php ?>
<div class="container">
  <h1 class="page-title">Checkout</h1>

  <?php if ($errors): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST">
    <div class="checkout-grid">
      <div>
        <div class="card">
          <h2 style="font-family:var(--font-display);font-size:1.2rem;margin-bottom:1.2rem;">Delivery Details</h2>
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" class="form-control" value="<?= h($current_user['name']) ?>" disabled>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control" value="<?= h($current_user['email']) ?>" disabled>
          </div>
          <div class="form-group">
            <label for="address">Shipping Address <span style="color:var(--accent)">*</span></label>
            <textarea id="address" name="address" class="form-control"
                      placeholder="Enter your full shipping address" rows="4"><?= h($address ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label for="notes">Order Notes (optional)</label>
            <textarea id="notes" name="notes" class="form-control"
                      placeholder="Any special instructions…" rows="2"><?= h($notes ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <div>
        <div class="card" style="position:sticky;top:84px;">
          <h2 style="font-family:var(--font-display);font-size:1.2rem;margin-bottom:1rem;">Order Summary</h2>
          <?php foreach ($items as $item): ?>
            <div style="display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid var(--line);font-size:.875rem;">
              <span><?= h($item['name']) ?> × <?= $item['qty'] ?></span>
              <strong><?= money($item['subtotal']) ?></strong>
            </div>
          <?php endforeach; ?>
          <div style="display:flex;justify-content:space-between;padding:.75rem 0;font-size:1.15rem;font-weight:700;margin-top:.5rem;">
            <span>Total</span>
            <span style="color:var(--accent-2)"><?= money($total) ?></span>
          </div>
          <div class="alert alert-info" style="margin-top:1rem;font-size:.8rem;">
            🔒 No payment required — this is a demo store.
          </div>
          <button type="submit" class="btn btn-primary"
                  style="width:100%;justify-content:center;padding:.8rem;font-size:1rem;margin-top:.75rem;">
            Place Order
          </button>
        </div>
      </div>
    </div>
  </form>
</div>
