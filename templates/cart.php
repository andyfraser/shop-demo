<?php // templates/cart.php ?>
<div class="container">
  <h1 class="page-title">Shopping Cart</h1>

  <?php if ($flash_success): ?>
    <div class="alert alert-success"><?= h($flash_success) ?></div>
  <?php endif; ?>

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
                <?php foreach ($items as $item): ?>
                  <tr data-item-id="<?= $item['id'] ?>">
                    <td style="width:80px;">
                      <?php product_img($item['image'] ?? '', $item['name'], 'cart-thumb') ?>
                    </td>
                    <td>
                      <a href="/product/<?= h($item['slug']) ?>" class="cart-product-name">
                        <?= h($item['name']) ?>
                      </a>
                    </td>
                    <td><?= money($item['price']) ?></td>
                    <td>
                      <input type="number" name="qty[<?= $item['id'] ?>]" value="<?= $item['qty'] ?>" min="0"
                        max="<?= $item['stock'] ?>" class="form-control qty-ctrl">
                    </td>
                    <td class="item-subtotal" data-item-id="<?= $item['id'] ?>"><strong><?= money($item['subtotal']) ?></strong></td>
                    <td>
                      <button type="submit" name="remove" value="<?= $item['id'] ?>" class="btn btn-outline btn-sm"
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
          <div
            style="display:flex;justify-content:space-between;padding:.75rem 0;font-size:1.2rem;font-weight:700;margin-bottom:1.2rem;">
            <span>Total</span><span id="cart-total" style="color:var(--accent-2)"><?= money($total) ?></span>
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