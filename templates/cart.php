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
                  $maxStock = $v ? $v->stock : $p->stock;
                ?>
                  <tr data-item-key="<?= h($key) ?>">
                    <td style="width:80px;">
                      <?php product_img($p->image ?? '', $p->name, 'cart-thumb', '', 'thumb') ?>
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
                    </td>
                    <td><?= money($item->unit_price) ?></td>
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
          <h2 style="font-family:var(--font-display);font-size:1.2rem;margin-bottom:1rem;">Order Summary</h2>
          <div
            style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--line);margin-bottom:.5rem;">
            <span>Subtotal</span><strong id="cart-subtotal"><?= money($total) ?></strong>
          </div>
          
          <div id="discount-row" style="display: <?= $discount > 0 ? 'flex' : 'none' ?>; justify-content:space-between; padding:.5rem 0; border-bottom:1px solid var(--line); margin-bottom:.5rem; color:var(--accent);">
            <span id="discount-label">Discount <?= $applied_promotion ? '(' . h($applied_promotion->name) . ')' : '' ?></span>
            <strong id="cart-discount">-<?= money($discount) ?></strong>
          </div>

          <div
            style="display:flex;justify-content:space-between;padding:.75rem 0;font-size:1.2rem;font-weight:700;margin-bottom:1.2rem;">
            <span>Total</span><span id="cart-total" style="color:var(--accent-2)"><?= money($grand_total) ?></span>
          </div>
          <div style="font-size:.85rem;color:var(--ink-2);margin-bottom:1.2rem;text-align:right;">
            Includes <span id="cart-vat"><?= money($total_vat) ?></span> VAT
          </div>

          <form action="/cart/promo" method="POST" style="margin-bottom:1.5rem;">
            <?= csrf_field() ?>
            <label style="font-size:0.85rem;margin-bottom:0.5rem;display:block;">Promo Code</label>
            <div style="display:flex;gap:0.5rem;">
              <input type="text" name="promo_code" class="form-control" placeholder="Enter code" 
                value="<?= $applied_promotion && $applied_promotion->code ? h($applied_promotion->code) : '' ?>"
                <?= $applied_promotion && $applied_promotion->code ? 'readonly' : '' ?>>
              <?php if ($applied_promotion && $applied_promotion->code): ?>
                <button type="submit" name="remove_promo" class="btn btn-outline btn-sm">Remove</button>
              <?php else: ?>
                <button type="submit" class="btn btn-outline btn-sm">Apply</button>
              <?php endif; ?>
            </div>
          </form>

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