<?php // templates/checkout.php ?>
<div class="container">
  <h1 class="page-title">Checkout</h1>

  <?php if ($errors): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST">
    <?= csrf_field() ?>
    <div class="checkout-grid">
      <div>
        <div class="card">
          <h2 style="font-family:var(--font-display);font-size:1.2rem;margin-bottom:1.2rem;">Delivery Details</h2>
          <div class="form-group">
            <label for="name">Full Name <span style="color:var(--accent)">*</span></label>
            <input type="text" id="name" name="name" class="form-control" value="<?= h($name ?? '') ?>" <?= !$is_guest ? 'readonly' : '' ?> required>
          </div>
          <div class="form-group">
            <label for="email">Email <span style="color:var(--accent)">*</span></label>
            <input type="email" id="email" name="email" class="form-control" value="<?= h($email ?? '') ?>" <?= !$is_guest ? 'readonly' : '' ?> required>
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

        <div class="card" style="margin-top:1.5rem;">
          <h2 style="font-family:var(--font-display);font-size:1.2rem;margin-bottom:1.2rem;">Delivery Method <span style="color:var(--accent)">*</span></h2>
          <?php if (empty($delivery_options)): ?>
            <div class="alert alert-error">No delivery options available. Please contact support.</div>
          <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:.75rem;">
              <?php foreach ($delivery_options as $opt): ?>
                <label style="display:flex;align-items:center;gap:.75rem;padding:.75rem;border:1px solid var(--line);border-radius:var(--radius);cursor:pointer;transition:border-color .2s;" class="delivery-opt">
                  <input type="radio" name="delivery_option_id" value="<?= $opt['id'] ?>" 
                         <?= ($delivery_id == $opt['id']) ? 'checked' : '' ?> required
                         onchange="updateTotal(<?= $opt['price'] ?>)">
                  <div style="flex:1">
                    <div style="font-weight:500;"><?= h($opt['name']) ?></div>
                  </div>
                  <strong><?= money($opt['price']) ?></strong>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
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
          <div style="display:flex;justify-content:space-between;padding:.4rem 0;font-size:.875rem;margin-top:.5rem;">
            <span>Subtotal</span>
            <strong><?= money($total) ?></strong>
          </div>
          <div id="delivery-row" style="display:none;justify-content:space-between;padding:.4rem 0;font-size:.875rem;">
            <span>Delivery</span>
            <strong id="delivery-cost"></strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:.75rem 0;font-size:1.15rem;font-weight:700;margin-top:.5rem;border-top:2px solid var(--line);">
            <span>Total</span>
            <span id="final-total" style="color:var(--accent-2)"><?= money($total) ?></span>
          </div>
          <div class="alert alert-info" style="margin-top:1rem;font-size:.8rem;">
            🔒 No payment required — this is a demo store.
          </div>
          <button type="submit" id="place-order-btn" class="btn btn-primary"
                  style="width:100%;justify-content:center;padding:.8rem;font-size:1rem;margin-top:.75rem;" disabled>
            Place Order
          </button>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
const baseTotal = <?= (float)$total ?>;
const currencySymbol = '<?= setting('currency_symbol') ?>';

function updateTotal(price) {
  document.getElementById('delivery-row').style.display = 'flex';
  document.getElementById('delivery-cost').innerText = currencySymbol + price.toFixed(2);
  document.getElementById('final-total').innerText = currencySymbol + (baseTotal + price).toFixed(2);
  document.getElementById('place-order-btn').disabled = false;
}

// Handle initial selection if errors occurred and page reloaded
window.addEventListener('load', () => {
  const checked = document.querySelector('input[name="delivery_option_id"]:checked');
  if (checked) {
    const price = parseFloat(checked.closest('label').querySelector('strong').innerText.replace(currencySymbol, ''));
    updateTotal(price);
  }
});
</script>
