<?php // templates/checkout.php ?>
<div class="container">
  <h1 class="page-title">Checkout</h1>

  <?php if ($errors): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST" id="checkout-form"
        data-base-total="<?= (float)$grand_total ?>"
        data-base-vat="<?= (float)($total_item_vat * ($total > 0 ? (1 - ($discount / $total)) : 1)) ?>"
        data-vat-rate="<?= settings()->default_vat_rate ?>"
        data-currency-symbol="<?= settings()->currency_symbol ?>">
    <?= csrf_field() ?>
    <div class="checkout-grid">
      <div>
        <div class="card">
          <h2 style="font-family:var(--font-display);font-size:1.2rem;margin-bottom:1.2rem;">Delivery Details</h2>
          
          <?php if (!empty($addresses)): ?>
            <div class="form-group">
              <label for="address-selector">Saved Addresses</label>
              <select id="address-selector" class="form-control">
                <option value="">— Use a new address —</option>
                <?php foreach ($addresses as $addr): ?>
                  <option value="<?= $addr->id ?>" 
                          data-name="<?= h($addr->name) ?>"
                          data-address="<?= h($addr->address) ?>"
                          data-city="<?= h($addr->city) ?>"
                          data-postcode="<?= h($addr->postcode) ?>"
                          data-country="<?= h($addr->country) ?>"
                          <?= $addr->isDefault() ? 'selected' : '' ?>>
                    <?= h($addr->label ?? 'Address') ?> (<?= h($addr->postcode) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid var(--line);">
          <?php endif; ?>

          <div class="form-group">
            <label for="name">Full Name <span style="color:var(--accent)">*</span></label>
            <input type="text" id="name" name="name" class="form-control" value="<?= h($name ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label for="email">Email <span style="color:var(--accent)">*</span></label>
            <input type="email" id="email" name="email" class="form-control" value="<?= h($email ?? '') ?>" <?= !$is_guest ? 'readonly' : '' ?> required>
          </div>
          <div class="form-group">
            <label for="address">Street Address <span style="color:var(--accent)">*</span></label>
            <textarea id="address" name="address" class="form-control"
                      placeholder="Enter your street address" rows="2" required><?= h($address ?? '') ?></textarea>
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
              <label for="city">City <span style="color:var(--accent)">*</span></label>
              <input type="text" id="city" name="city" class="form-control" value="<?= h($city ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label for="postcode">Postcode <span style="color:var(--accent)">*</span></label>
              <input type="text" id="postcode" name="postcode" class="form-control" value="<?= h($postcode ?? '') ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label for="country">Country <span style="color:var(--accent)">*</span></label>
            <input type="text" id="country" name="country" class="form-control" value="<?= h($country ?? 'United Kingdom') ?>" required>
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
                  <input type="radio" name="delivery_option_id" value="<?= $opt->id ?>" 
                         <?= ($delivery_id == $opt->id) ? 'checked' : '' ?> required
                         onchange="updateTotal(<?= $opt->price ?>)">
                  <div style="flex:1">
                    <div style="font-weight:500;"><?= h($opt->name) ?></div>
                  </div>
                  <strong><?= money($opt->price) ?></strong>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <div class="card" style="position:sticky;top:84px;">
          <h2 style="font-family:var(--font-display);font-size:1.2rem;margin-bottom:1rem;">Order Summary</h2>
          <?php foreach ($items as $item): 
             $p = $item->product;
             $v = $item->variant;
          ?>
            <div style="display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid var(--line);font-size:.875rem;">
              <span>
                <?= h($p->name) ?>
                <?php if ($v): ?>
                  <div style="font-size:0.75rem;color:var(--ink-2);margin-top:0.1rem;">Option: <?= h($v->name) ?></div>
                <?php endif; ?>
                × <?= $item->qty ?>
              </span>
              <strong><?= money($item->getSubtotal()) ?></strong>
            </div>
          <?php endforeach; ?>
          <div style="display:flex;justify-content:space-between;padding:.4rem 0;font-size:.875rem;margin-top:.5rem;">
            <span>Subtotal</span>
            <strong><?= money($total) ?></strong>
          </div>
          <?php if ($discount > 0): ?>
            <div id="applied-promos" style="margin-top:.5rem; border-top:1px solid var(--line); padding-top:.5rem;">
              <?php foreach ($applied_promotions as $promo): ?>
                <div style="display:flex;justify-content:space-between;padding:.4rem 0;font-size:.875rem;color:var(--accent);">
                  <span>Discount (<?= h($promo->name) ?>)</span>
                  <strong>-<?= money($cart->getPromotionDiscount($promo)) ?></strong>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <div id="delivery-row" style="display:none;justify-content:space-between;padding:.4rem 0;font-size:.875rem;">
            <span>Delivery</span>
            <strong id="delivery-cost"></strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:.75rem 0;font-size:1.15rem;font-weight:700;margin-top:.5rem;border-top:2px solid var(--line);">
            <span>Total</span>
            <span id="final-total" style="color:var(--accent-2)"><?= money($grand_total) ?></span>
          </div>
          <div id="vat-row" style="font-size:.85rem;color:var(--ink-2);text-align:right;margin-top:.25rem;">
            Includes <span id="vat-amount"><?= money($total_item_vat * ($total > 0 ? (1 - ($discount / $total)) : 1)) ?></span> VAT
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
