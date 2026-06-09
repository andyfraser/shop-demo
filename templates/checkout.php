<?php // templates/checkout.php ?>
<div class="container">
  <h1 class="page-title">Checkout</h1>

  <?php if ($errors): ?>
    <?php foreach ($errors as $e): ?>
      <?= (new \App\View\Components\Alert($e, 'error'))->render() ?>
    <?php endforeach; ?>
  <?php endif; ?>

  <form method="POST" id="checkout-form"
        data-base-total="<?= (float)$grand_total ?>"
        data-base-vat="<?= (float)($total_item_vat * ($total > 0 ? (1 - ($discount / $total)) : 1)) ?>"
        data-vat-rate="<?= settings()->default_vat_rate ?>"
        data-currency-symbol="<?= settings()->currency_symbol ?>"
        data-is-virtual-only="<?= $is_virtual_only ? '1' : '0' ?>">
    <?= csrf_field() ?>
    <div class="checkout-grid">
      <div>
        <!-- Delivery Details Card -->
        <div class="card" id="delivery-details-card" style="<?= $is_virtual_only ? 'display: none;' : '' ?>">
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
                      placeholder="Enter your street address" rows="2" <?= !$is_virtual_only ? 'required' : '' ?>><?= h($address ?? '') ?></textarea>
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
              <label for="city">City <span style="color:var(--accent)">*</span></label>
              <input type="text" id="city" name="city" class="form-control" value="<?= h($city ?? '') ?>" <?= !$is_virtual_only ? 'required' : '' ?>>
            </div>
            <div class="form-group">
              <label for="postcode">Postcode <span style="color:var(--accent)">*</span></label>
              <input type="text" id="postcode" name="postcode" class="form-control" value="<?= h($postcode ?? '') ?>" <?= !$is_virtual_only ? 'required' : '' ?>>
            </div>
          </div>
          <div class="form-group">
            <label for="country">Country <span style="color:var(--accent)">*</span></label>
            <input type="text" id="country" name="country" class="form-control" value="<?= h($country ?? 'United Kingdom') ?>" <?= !$is_virtual_only ? 'required' : '' ?>>
          </div>
          <div class="form-group">
            <label for="notes">Order Notes (optional)</label>
            <textarea id="notes" name="notes" class="form-control"
                      placeholder="Any special instructions…" rows="2"><?= h($notes ?? '') ?></textarea>
          </div>
        </div>

        <!-- Billing Info if Digital Only -->
        <?php if ($is_virtual_only): ?>
          <div class="card" id="billing-details-card">
            <h2 style="font-family:var(--font-display);font-size:1.2rem;margin-bottom:1.2rem;">Billing Details</h2>
            <div class="form-group">
              <label for="name">Full Name <span style="color:var(--accent)">*</span></label>
              <input type="text" id="name" name="name" class="form-control" value="<?= h($name ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label for="email">Email <span style="color:var(--accent)">*</span></label>
              <input type="email" id="email" name="email" class="form-control" value="<?= h($email ?? '') ?>" <?= !$is_guest ? 'readonly' : '' ?> required>
            </div>
            <div class="form-group">
              <label for="notes">Order Notes (optional)</label>
              <textarea id="notes" name="notes" class="form-control"
                        placeholder="Any special instructions…" rows="2"><?= h($notes ?? '') ?></textarea>
            </div>
            <div class="alert alert-success" style="margin-top:1rem; font-size:0.875rem;">
              ⚡ <strong>Virtual Order Bypass:</strong> Since your cart contains only virtual/digital items, no physical shipping address is required. Your items will be delivered immediately via email.
            </div>
          </div>
        <?php endif; ?>

        <!-- Delivery Method Card -->
        <div class="card" id="delivery-method-card" style="margin-top:1.5rem; <?= $is_virtual_only ? 'display: none;' : '' ?>">
          <h2 style="font-family:var(--font-display);font-size:1.2rem;margin-bottom:1.2rem;">Delivery Method <span style="color:var(--accent)">*</span></h2>
          <?php if (empty($delivery_options) && !$is_virtual_only): ?>
            <?= (new \App\View\Components\Alert('No delivery options available. Please contact support.', 'error'))->render() ?>
          <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:.75rem;">
              <?php foreach ($delivery_options as $opt): ?>
                <label style="display:flex;align-items:center;gap:.75rem;padding:.75rem;border:1px solid var(--line);border-radius:var(--radius);cursor:pointer;transition:border-color .2s;" class="delivery-opt">
                  <input type="radio" name="delivery_option_id" value="<?= $opt->id ?>"
                          <?= ($delivery_id == $opt->id) ? 'checked' : '' ?> <?= !$is_virtual_only ? 'required' : '' ?>>
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
          <?= new \App\View\Components\OrderSummary($cart, true, $items, $gift_card_discount ?? 0.0) ?>

          <!-- Gift Card Input Card Section -->
          <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px dashed var(--line);">
            <label for="gift-card-code" style="font-size: 0.85rem; font-weight: 600; color: var(--ink); display: block; margin-bottom: 0.5rem;">
              Have a Gift Card?
            </label>
            <div style="display: flex; gap: 0.5rem;">
              <input type="text" id="gift-card-code" name="gift_card_code" class="form-control" 
                     placeholder="GIFT-XXXX-XXXX" value="<?= h($gift_card_code ?? '') ?>" 
                     style="font-family: monospace; font-size: 0.9rem; text-transform: uppercase;">
              <button type="button" id="apply-gift-card-btn" class="btn" style="padding: 0.5rem 1rem; font-size: 0.875rem; background: var(--bg-hover); border: 1px solid var(--line); cursor: pointer; transition: background 0.2s;">
                Apply
              </button>
            </div>
            <div id="gift-card-message" style="font-size: 0.75rem; margin-top: 0.4rem; display: none;"></div>
          </div>

          <!-- Secure Payment Simulated Hosted Element -->
          <?php if ((settings()->payment_gateway ?? 'mock_card') === 'mock_card'): ?>
          <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px dashed var(--line);" id="payment-gateway-container">
            <label style="font-size: 0.85rem; font-weight: 600; color: var(--ink); display: block; margin-bottom: 0.5rem;">
              Secure Card Payment (Simulated)
            </label>
            <div id="secure-payment-element" style="border: 1px solid var(--line); border-radius: var(--radius); padding: 0.75rem 1rem; background: var(--bg); transition: border-color 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; gap: 0.75rem;">
              <div style="display: flex; align-items: center; gap: 0.5rem; border-bottom: 1px solid var(--line); padding-bottom: 0.5rem;">
                <span style="font-size: 1.1rem; filter: grayscale(1);">💳</span>
                <input type="text" id="card_number" name="card_number" placeholder="4242 4242 4242 4242" required maxlength="19" autocomplete="cc-number"
                       style="border: none; background: transparent; outline: none; width: 100%; font-family: monospace; font-size: 0.95rem; color: var(--ink);">
                <span id="card-brand-logo" style="font-weight: 600; font-size: 0.75rem; color: var(--accent); text-transform: uppercase; letter-spacing: 0.05em;"></span>
              </div>
              <div style="display: flex; gap: 1rem;">
                <div style="flex: 1; display: flex; align-items: center; gap: 0.5rem;">
                  <span style="font-size: 0.8rem; color: var(--ink-2); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Exp</span>
                  <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/YY" required maxlength="5" autocomplete="cc-exp"
                         style="border: none; background: transparent; outline: none; width: 100%; font-family: monospace; font-size: 0.95rem; color: var(--ink);">
                </div>
                <div style="flex: 1; display: flex; align-items: center; gap: 0.5rem; border-left: 1px solid var(--line); padding-left: 1rem;">
                  <span style="font-size: 0.8rem; color: var(--ink-2); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">CVC</span>
                  <input type="text" id="card_cvc" name="card_cvc" placeholder="123" required maxlength="4" autocomplete="cc-csc"
                         style="border: none; background: transparent; outline: none; width: 100%; font-family: monospace; font-size: 0.95rem; color: var(--ink);">
                </div>
              </div>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem; font-size: 0.7rem; color: var(--ink-2);">
              <span>🔒 Encrypted Sandboxed Gateway</span>
              <span style="font-weight: 600; color: var(--accent);">Mock Mode</span>
            </div>

            <!-- Simulated Card Controls Guide -->
            <div style="margin-top: 1rem; background: var(--bg-2); border: 1px solid var(--line); border-radius: var(--radius); padding: 0.75rem; font-size: 0.75rem; line-height: 1.4;">
              <strong style="color: var(--accent); display: block; margin-bottom: 0.25rem;">💡 Sandbox Testing Codes:</strong>
              Use card <code style="background: var(--bg-hover); padding: 1px 4px; border-radius: 3px; font-family: monospace;">4242 4242 4242 4242</code>.
              <div style="margin-top: 0.25rem;">Enter one of these CVC numbers to test responses:</div>
              <ul style="margin: 0.25rem 0 0 1rem; padding: 0; list-style-type: none;">
                <li>• <code style="font-family: monospace; font-weight: 600;">999</code>: Insufficient Funds (Decline)</li>
                <li>• <code style="font-family: monospace; font-weight: 600;">998</code>: Gateway Timeout</li>
                <li>• <code style="font-family: monospace; font-weight: 600;">997</code>: Expired Card</li>
                <li>• <code style="font-family: monospace; font-weight: 600;">996</code>: Suspected Fraud</li>
                <li>• Any other CVC: Payment success &rarr; Order status: <strong>Paid</strong></li>
              </ul>
            </div>
          </div>
          <?php endif; ?>

          <script>
            document.addEventListener('DOMContentLoaded', function() {
              const widget = document.getElementById('secure-payment-element');
              if (!widget) return; // Exit if gateway widget is not active/rendered (e.g. manual bypass)
              
              const cardNum = document.getElementById('card_number');
              const cardExp = document.getElementById('card_expiry');
              const cardCvc = document.getElementById('card_cvc');
              const brandLogo = document.getElementById('card-brand-logo');

              // Highlight/focus container wrapper to mimic Hosted Fields iframe focus
              [cardNum, cardExp, cardCvc].forEach(input => {
                input.addEventListener('focus', () => {
                  widget.style.borderColor = 'var(--accent)';
                  widget.style.boxShadow = '0 0 0 2px rgba(var(--accent-rgb), 0.15)';
                });
                input.addEventListener('blur', () => {
                  widget.style.borderColor = 'var(--line)';
                  widget.style.boxShadow = 'none';
                });
              });

              // Format card number to groups of 4 digits
              cardNum.addEventListener('input', function(e) {
                let val = e.target.value.replace(/\D/g, '');
                if (val.length > 0) {
                  // Detect Brand
                  if (val.startsWith('4')) {
                    brandLogo.textContent = 'Visa';
                  } else if (val.startsWith('5')) {
                    brandLogo.textContent = 'Mastercard';
                  } else if (val.startsWith('3')) {
                    brandLogo.textContent = 'Amex';
                  } else {
                    brandLogo.textContent = '';
                  }
                } else {
                  brandLogo.textContent = '';
                }

                let formatted = val.match(/.{1,4}/g);
                e.target.value = formatted ? formatted.join(' ') : '';
              });

              // Format expiry input: MM/YY
              cardExp.addEventListener('input', function(e) {
                let val = e.target.value.replace(/\D/g, '');
                if (val.length >= 2) {
                  e.target.value = val.substring(0, 2) + '/' + val.substring(2, 4);
                } else {
                  e.target.value = val;
                }
              });

              // Only allow digits in CVC
              cardCvc.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '');
              });
            });
          </script>

          <!-- Security Alert Info -->
          <div class="alert alert-info" style="margin-top:1rem;font-size:.8rem;">
            🔒 Safe payment processing simulator — no actual funds are charged.
          </div>
          <button type="submit" id="place-order-btn" class="btn btn-primary"
                  style="width:100%;justify-content:center;padding:.8rem;font-size:1rem;margin-top:.75rem;" 
                  <?= ($is_virtual_only || $delivery_id) ? '' : 'disabled' ?>>
            Place Order
          </button>
        </div>
      </div>
    </div>
  </form>
</div>
