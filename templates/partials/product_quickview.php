<?php // templates/partials/product_quickview.php
// Variables expected: $product (Product), $is_logged_in (bool), $is_in_wishlist (bool), $avg_rating (float)
?>
<div class="qv-modal-content-inner">
  <div class="qv-modal-image">
    <?php product_img($product->image ?? '', $product->name, '', 'width: 100%; height: 100%; object-fit: cover;') ?>
  </div>

  <div class="qv-modal-meta">
    <?php if ($product->cat_name): ?>
      <div class="qv-cat" style="margin-bottom: 0.25rem;">
        <a href="/category/<?= h($product->cat_slug) ?>"
           style="color:var(--accent);font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;text-decoration:none;">
          <?= h($product->cat_name) ?>
        </a>
      </div>
    <?php endif; ?>

    <h2 class="qv-title" style="font-family: var(--font-display); font-size: 1.6rem; font-weight: 600; line-height: 1.25; margin: 0 0 0.5rem 0; color: var(--ink);">
      <?= h($product->name) ?>
    </h2>

    <?php if ($avg_rating > 0): ?>
      <div class="qv-rating" style="color:var(--gold); font-size: 0.95rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.25rem;">
        <?php
          $full_stars = floor($avg_rating);
          $half_star = ($avg_rating - $full_stars) >= 0.5 ? 1 : 0;
          $empty_stars = 5 - $full_stars - $half_star;
          echo str_repeat('★', $full_stars) . ($half_star ? '½' : '') . str_repeat('☆', $empty_stars);
        ?>
        <span style="color:var(--ink-2); font-size:0.8rem; margin-left:0.25rem;">(<?= number_format($avg_rating, 1) ?>/5)</span>
      </div>
    <?php endif; ?>

    <div class="qv-price" id="qv-display-price" style="font-size: 1.4rem; font-weight: 700; color: var(--accent-2); margin-bottom: 1rem;">
      <?= money($product->price) ?>
    </div>

    <?php if ($promo = get_active_promotion($product)): ?>
      <div class="promo-callout" style="margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.9rem; border-radius: var(--radius); background: #fff8f5; border: 1px solid rgba(200,98,42,0.2);">
        <div class="promo-icon" style="font-size: 1.2rem;">🏷️</div>
        <div class="promo-text" style="flex: 1;">
          <h4 style="font-family: var(--font-display); font-size: 0.85rem; margin: 0 0 0.05rem 0; color: var(--accent-2);"><?= h($promo->name) ?></h4>
          <?php if ($promo->description || $promo->min_order_amount > 0 || $promo->type === 'buy_x_get_y'): ?>
            <p style="font-size: 0.75rem; color: var(--ink-2); margin: 0; line-height: 1.3;">
              <?php if ($promo->type === 'buy_x_get_y'): ?>
                <strong>Buy <?= $promo->buy_qty ?>, Get <?= $promo->get_qty ?> 
                <?= $promo->value >= 100 ? 'FREE' : (int)$promo->value . '% OFF' ?></strong>
                <?php if ($promo->description): ?><br><?= h($promo->description) ?><?php endif; ?>
              <?php else: ?>
                <?= h($promo->description) ?>
              <?php endif; ?>

              <?php if ($promo->min_order_amount > 0): ?>
                <?= ($promo->description || $promo->type === 'buy_x_get_y') ? ' &bull; ' : '' ?>
                Min. spend <?= money($promo->min_order_amount) ?>
              <?php endif; ?>
            </p>
          <?php endif; ?>
        </div>
        <div class="promo-badge" style="background: var(--accent); color: var(--white); padding: 0.15rem 0.45rem; font-size: 0.65rem; font-weight: 700; border-radius: 2px; text-transform: uppercase;">
          <?php if ($promo->type === 'percentage'): ?>
            <?= (int)$promo->value ?>% OFF
          <?php elseif ($promo->type === 'fixed_amount'): ?>
            SALE
          <?php elseif ($promo->type === 'buy_x_get_y'): ?>
            BOGO
          <?php else: ?>
            OFFER
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($product->description): ?>
      <p class="qv-desc" style="font-size: 0.85rem; color: var(--ink-2); line-height: 1.5; margin: 0 0 1.25rem 0; max-height: 100px; overflow-y: auto; padding-right: 0.25rem;">
        <?= nl2br(h($product->description)) ?>
      </p>
    <?php endif; ?>

    <div id="qv-stock-status" style="margin-bottom: 1.25rem;">
      <?php 
        $availableStock = $product->getAvailableStock();
        if ($product->is_virtual):
      ?>
        <?= (new \App\View\Components\StatusBadge('✓ In Stock', 'badge-success'))->render() ?>
      <?php elseif ($availableStock > settings()->low_stock_threshold): ?>
        <?= (new \App\View\Components\StatusBadge('✓ In Stock', 'badge-success'))->render() ?>
      <?php elseif ($availableStock > 0): ?>
        <?= (new \App\View\Components\StatusBadge('⚠ Only ' . $availableStock . ' left', 'badge-warning'))->render() ?>
      <?php else: ?>
        <?= (new \App\View\Components\StatusBadge('✗ Out of Stock', 'badge-danger'))->render() ?>
      <?php endif; ?>
    </div>

    <?php if (!empty($product->variants)): ?>
      <div class="form-group" style="margin-bottom: 1.25rem;">
        <label for="qv-variant-select" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Option</label>
        <select id="qv-variant-select" class="form-control" style="width: 100%; max-width: 300px; padding: 0.4rem 0.6rem; border: 1px solid var(--border); border-radius: var(--radius); font-size: 0.875rem;"
                data-low-stock-threshold="<?= settings()->low_stock_threshold ?>">
          <?php if ($product->force_variant): ?>
            <option value="" disabled selected>— Please choose —</option>
          <?php else: ?>
            <option value="" 
                    data-price="<?= $product->price ?>" 
                    data-stock="<?= $product->is_virtual ? 999999 : $product->stock ?>">
              Default (<?= money($product->price) ?>)
            </option>
          <?php endif; ?>
          <?php foreach ($product->variants as $v): ?>
            <option value="<?= $v->id ?>" 
                    data-price="<?= $v->getEffectivePrice($product->price) ?>" 
                    data-stock="<?= $product->is_virtual ? 999999 : $v->stock ?>">
              <?= h($v->name) ?> (<?= $v->formattedPrice($product->price) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <?php if ($product->is_virtual || $availableStock > 0 || !empty($product->variants)): ?>
      <div id="qv-cart-message" style="margin-bottom: 0.75rem;"></div>
      <form method="POST" id="qv-add-to-cart-form">
          <?= csrf_field() ?>
          <input type="hidden" name="product_id" value="<?= $product->id ?>">
          <input type="hidden" name="variant_id" id="qv-selected-variant-id" value="">
          <input type="hidden" name="slug" value="<?= h($product->slug) ?>">

        <?php if ($product->is_virtual && $product->virtual_type === 'giftcard'): ?>
          <div class="gift-card-recipient-section" style="margin-bottom: 1.25rem; padding: 1rem; border: 1px solid var(--line); border-radius: var(--radius); background: var(--bg-card); font-size: 0.825rem;">
            <h4 style="margin-top: 0; margin-bottom: 0.75rem; color: var(--accent); font-size: 0.9rem; font-weight: 600;">🎁 Gift Card Details</h4>
            
            <div class="form-group" style="margin-bottom: 0.75rem;">
              <label for="qv_recipient_email" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Recipient Email *</label>
              <input type="email" id="qv_recipient_email" name="recipient_email" class="form-control" style="width: 100%; font-size: 0.85rem; padding: 0.35rem 0.5rem;" placeholder="friend@example.com" required>
            </div>

            <div class="form-group" style="margin-bottom: 0.75rem;">
              <label for="qv_sender_name" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Sender Name (optional)</label>
              <input type="text" id="qv_sender_name" name="sender_name" class="form-control" style="width: 100%; font-size: 0.85rem; padding: 0.35rem 0.5rem;" placeholder="Your Name">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
              <label for="qv_message" style="display: block; font-weight: 600; margin-bottom: 0.25rem;">Personal Message (optional)</label>
              <textarea id="qv_message" name="message" class="form-control" style="width: 100%; height: 50px; resize: none; font-size: 0.85rem; padding: 0.35rem 0.5rem;" placeholder="Enjoy this gift card!"></textarea>
            </div>
          </div>
        <?php endif; ?>

        <div class="qty-row" style="display: flex; gap: 1rem; align-items: flex-end;">
          <div class="form-group" style="margin:0; flex-shrink: 0;">
            <label for="qv-qty" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Quantity</label>
            <input type="number" id="qv-qty" name="qty" class="form-control qty-input" style="width: 70px; text-align: center; font-size: 0.875rem; padding: 0.4rem 0.5rem;"
                   value="1" min="1" max="<?= $product->is_virtual ? 999999 : $availableStock ?>">
          </div>
          <button type="submit" id="qv-add-to-cart-btn" name="add_to_cart" class="btn btn-primary" style="flex: 1; justify-content: center; font-size: 0.875rem; padding: 0.6rem 1rem;" <?= $product->force_variant && !empty($product->variants) ? 'disabled' : '' ?>>
            Add to Cart
          </button>
        </div>
      </form>
    <?php endif; ?>

    <div class="qv-view-details-link" style="margin-top: 1.25rem; border-top: 1px solid var(--line); padding-top: 1rem;">
      <a href="/product/<?= h($product->slug) ?>" class="btn btn-outline btn-block" style="justify-content: center; display: flex; width: 100%; font-size: 0.875rem; padding: 0.6rem 1rem;">
        View Full Product Details →
      </a>
    </div>
  </div>
</div>
