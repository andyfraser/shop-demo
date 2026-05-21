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
                <?php if ($item->metadata): 
                  $meta = json_decode($item->metadata, true);
                  if (!empty($meta['recipient_email'])):
                ?>
                  <div style="font-size:0.75rem;color:var(--ink-2);margin-top:0.2rem;background:var(--bg-2);padding:0.3rem 0.5rem;border-radius:4px;border:1px solid var(--line);max-width:220px;">
                    <div style="font-weight:600;color:var(--accent);">Gift Card:</div>
                    To: <?= h($meta['recipient_email']) ?>
                    <?php if (!empty($meta['sender_name'])): ?><br>From: <?= h($meta['sender_name']) ?><?php endif; ?>
                    <?php if (!empty($meta['message'])): ?><br><em style="font-size:0.7rem;">"<?= h($meta['message']) ?>"</em><?php endif; ?>
                  </div>
                <?php 
                  endif;
                endif; ?>
                × <?= $item->qty ?>
              </span>
              <strong><?= money($item->getSubtotal()) ?></strong>
            </div>
          <?php endforeach; ?>
          <div style="display:flex;justify-content:space-between;padding:.4rem 0;font-size:.875rem;margin-top:.5rem;">
            <span>Subtotal</span>
            <strong><?= money($total) ?></strong>
          </div>
          
          <!-- Promo Code Row -->
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
          
          <!-- Delivery Cost Row -->
          <div id="delivery-row" style="display:none;justify-content:space-between;padding:.4rem 0;font-size:.875rem;">
            <span>Delivery</span>
            <strong id="delivery-cost"></strong>
          </div>

          <!-- Gift Card Applied Row -->
          <div id="gift-card-row" style="display: <?= ($gift_card_discount > 0) ? 'flex' : 'none' ?>; justify-content: space-between; padding: 0.4rem 0; font-size: 0.875rem; color: var(--accent-2); border-top: 1px solid var(--line); margin-top: 0.5rem; padding-top: 0.5rem;">
            <span>Gift Card Applied</span>
            <strong id="gift-card-amount">-<?= money($gift_card_discount ?? 0.0) ?></strong>
          </div>

          <!-- Final Total Row -->
          <div style="display:flex;justify-content:space-between;padding:.75rem 0;font-size:1.15rem;font-weight:700;margin-top:.5rem;border-top:2px solid var(--line);">
            <span>Total</span>
            <span id="final-total" style="color:var(--accent-2)"><?= money($grand_total) ?></span>
          </div>

          <!-- VAT Inclusion Row -->
          <div id="vat-row" style="font-size:.85rem;color:var(--ink-2);text-align:right;margin-top:.25rem;">
            Includes <span id="vat-amount"><?= money($total_item_vat * ($total > 0 ? (1 - ($discount / $total)) : 1)) ?></span> VAT
          </div>

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

          <!-- Security Alert Info -->
          <div class="alert alert-info" style="margin-top:1rem;font-size:.8rem;">
            🔒 No payment required — this is a demo store.
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
