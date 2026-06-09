<?php
/** @var \App\Services\CartServiceInterface $cart */
/** @var bool $isCheckout */
/** @var array $items */
/** @var float $total */
/** @var float $discount */
/** @var float $grandTotal */
/** @var array $applied_promotions */
/** @var float $giftCardDiscount */
/** @var float $deliveryCost */
/** @var float $vatAmount */
?>
<h2 style="font-family:var(--font-display);font-size:1.2rem;margin-bottom:1rem;">Order Summary</h2>

<?php if ($isCheckout && !empty($items)): ?>
  <!-- Checkout Items List -->
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
<?php endif; ?>

<!-- Subtotal Row -->
<div style="display:flex;justify-content:space-between;padding:.5rem 0;<?= ($isCheckout ? 'font-size:.875rem;margin-top:.5rem;' : 'border-bottom:1px solid var(--line);margin-bottom:.5rem;') ?>">
  <span>Subtotal</span>
  <strong id="<?= $isCheckout ? 'checkout-subtotal' : 'cart-subtotal' ?>"><?= money($total) ?></strong>
</div>

<!-- Promo/Discount Row -->
<?php if ($isCheckout): ?>
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
<?php else: ?>
  <div id="discount-row" style="display: <?= $discount > 0 ? 'block' : 'none' ?>; border-bottom:1px solid var(--line); margin-bottom:.5rem; color:var(--accent);">
    <div id="discount-details">
      <?php foreach ($applied_promotions as $promo): ?>
        <div style="display:flex; justify-content:space-between; padding:.25rem 0;">
          <span><?= h($promo->name) ?></span>
          <strong>-<?= money($cart->getPromotionDiscount($promo)) ?></strong>
        </div>
      <?php endforeach; ?>
    </div>
    <div id="discount-summary" style="display:none; justify-content:space-between; padding:.25rem 0; font-weight:bold;">
      <span id="discount-label">Discount (<?= h(implode(', ', array_map(fn($p) => $p->name, $applied_promotions))) ?>)</span>
      <strong id="cart-discount">-<?= money($discount) ?></strong>
    </div>
  </div>
<?php endif; ?>

<!-- Delivery Cost Row -->
<?php if ($isCheckout): ?>
  <div id="delivery-row" style="display:none;justify-content:space-between;padding:.4rem 0;font-size:.875rem;">
    <span>Delivery</span>
    <strong id="delivery-cost"></strong>
  </div>
<?php endif; ?>

<!-- Gift Card Applied Row -->
<?php if ($isCheckout): ?>
  <div id="gift-card-row" style="display: <?= ($giftCardDiscount > 0) ? 'flex' : 'none' ?>; justify-content: space-between; padding: 0.4rem 0; font-size: 0.875rem; color: var(--accent-2); border-top: 1px solid var(--line); margin-top: 0.5rem; padding-top: 0.5rem;">
    <span>Gift Card Applied</span>
    <strong id="gift-card-amount">-<?= money($giftCardDiscount) ?></strong>
  </div>
<?php endif; ?>

<!-- Final Total Row -->
<div style="display:flex;justify-content:space-between;padding:.75rem 0;<?= ($isCheckout ? 'font-size:1.15rem;font-weight:700;margin-top:.5rem;border-top:2px solid var(--line);' : 'font-size:1.2rem;font-weight:700;margin-bottom:1.2rem;') ?>">
  <span>Total</span>
  <span id="<?= $isCheckout ? 'final-total' : 'cart-total' ?>" style="color:var(--accent-2)"><?= money($grandTotal) ?></span>
</div>

<!-- VAT Inclusion Row -->
<div id="<?= $isCheckout ? 'vat-row' : 'cart-vat-row' ?>" style="font-size:.85rem;color:var(--ink-2);text-align:right;<?= ($isCheckout ? 'margin-top:.25rem;' : 'margin-bottom:1.2rem;') ?>">
  Includes <span id="<?= $isCheckout ? 'vat-amount' : 'cart-vat' ?>"><?= money($vatAmount) ?></span> VAT
</div>
