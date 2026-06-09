<?php // templates/cart.php ?>
<div class="container">
  <h1 class="page-title">Shopping Cart</h1>

  <?php if (empty($items)): ?>
    <div class="empty-state">
      <div class="icon">🛒</div>
      <h3>Your cart is empty</h3>
      <p style="margin-bottom:1.5rem;">Add some products to get started.</p>
      <a href="/" class="btn btn-primary">Browse Products</a>
    </div>

  <?php else: ?>
    <div class="two-col">
      <div>
        <div class="card" style="padding:0;">
          <form method="POST" id="cart-form">
            <?= csrf_field() ?>
            <div class="cart-table-scroll">
            <table class="cart-table">
              <thead>
                <tr>
                  <th colspan="2">Product</th>
                  <th>Price</th>
                  <th>Qty</th>
                  <th>Subtotal</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): 
                  $p = $item->product;
                  $v = $item->variant;
                  $key = $item->key;
                  $maxStock = $p->is_virtual ? 999999 : ($v ? $v->stock : $p->getAvailableStock());
                ?>
                  <tr data-item-key="<?= h($key) ?>">
                    <td style="width:80px;">
                      <?php product_img($p->image ?? '', $p->name, 'cart-thumb', '', 'thumb', '80px') ?>
                    </td>
                    <td>
                      <a href="/product/<?= h($p->slug) ?>" class="cart-product-name">
                        <?= h($p->name) ?>
                      </a>
                      <?php if ($v): ?>
                        <div style="font-size:0.85rem;color:var(--ink-2);margin-top:0.2rem;">
                          Option: <?= h($v->name) ?>
                        </div>
                      <?php endif; ?>
                      <?php if ($item->metadata): 
                        $meta = json_decode($item->metadata, true);
                        if (!empty($meta['recipient_email'])):
                      ?>
                        <div class="giftcard-details" style="font-size:0.8rem;color:var(--ink-2);margin-top:0.3rem;background:var(--bg-2);padding:0.4rem;border-radius:4px;border:1px solid var(--line);max-width:300px;">
                          <strong>Gift Card Details:</strong><br>
                          To: <?= h($meta['recipient_email']) ?><br>
                          <?php if (!empty($meta['sender_name'])): ?>
                            From: <?= h($meta['sender_name']) ?><br>
                          <?php endif; ?>
                          <?php if (!empty($meta['message'])): ?>
                            Note: <em><?= h($meta['message']) ?></em>
                          <?php endif; ?>
                        </div>
                      <?php 
                        endif;
                      endif; ?>
                    </td>
                    <td class="item-unit-price" data-item-key="<?= h($key) ?>"><?= money($item->unit_price) ?></td>
                    <td>
                      <input type="number" name="qty[<?= h($key) ?>]" value="<?= $item->qty ?>" min="0"
                        max="<?= $maxStock ?>" class="form-control qty-ctrl">
                    </td>
                    <td class="item-subtotal" data-item-key="<?= h($key) ?>"><strong><?= money($item->getSubtotal()) ?></strong></td>
                    <td>
                      <button type="submit" name="remove" value="<?= h($key) ?>" class="btn btn-outline btn-sm"
                        style="padding:.3rem .7rem;color:var(--accent)">✕</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            </div>
            <div style="padding:1rem;border-top:1px solid var(--line);">
              <button type="submit" name="update" class="btn btn-outline btn-sm">Update Cart</button>
            </div>
          </form>
        </div>
      </div>

      <div class="order-summary">
        <div class="card">
          <?= new \App\View\Components\OrderSummary($cartService, false) ?>

          <div class="promo-section" style="margin-bottom:1.5rem;">
            <form action="/cart/promo" method="POST">
              <?= csrf_field() ?>
              <label style="font-size:0.85rem;margin-bottom:0.5rem;display:block;">Promo Code</label>
              <div style="display:flex;gap:0.5rem;margin-bottom:0.5rem;">
                <input type="text" name="promo_code" class="form-control" placeholder="Enter code">
                <button type="submit" class="btn btn-outline btn-sm">Apply</button>
              </div>
            </form>

            <?php 
            $manualPromos = array_filter($applied_promotions, fn($p) => !empty($p->applied_code));
            if (!empty($manualPromos)): 
            ?>
              <div style="font-size:0.85rem; margin-top:0.75rem;">
                <div style="color:var(--ink-2); margin-bottom:0.4rem;">Applied Codes:</div>
                <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                  <?php foreach ($manualPromos as $promo): ?>
                    <form action="/cart/promo" method="POST" style="display:inline;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="promo_code" value="<?= h($promo->applied_code) ?>">
                      <span class="badge" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.25rem 0.5rem; background:var(--bg-2); border:1px solid var(--line); border-radius:4px;">
                        <?= h($promo->applied_code) ?>
                        <button type="submit" name="remove_promo" style="background:none; border:none; padding:0; cursor:pointer; color:var(--accent); font-weight:bold; font-size:1rem; line-height:1;">&times;</button>
                      </span>
                    </form>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <a href="/checkout" class="btn btn-primary" style="width:100%;justify-content:center;">
            Proceed to Checkout
          </a>
          <a href="<?= BASE_URL ?>/" class="btn btn-outline btn-sm"
            style="width:100%;justify-content:center;margin-top:.75rem;">
            Continue Shopping
          </a>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>