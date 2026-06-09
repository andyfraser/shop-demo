<?php // templates/product.php ?>

<div class="container">

  <?php
    $crumbs = ['Home' => BASE_URL . '/'];
    foreach ($breadcrumb as $c) {
        $crumbs[] = [
            'label' => $c->name,
            'url' => "/category/{$c->slug}"
        ];
    }
    $crumbs[] = $product->name;
  ?>
  <?= new \App\View\Components\Breadcrumbs($crumbs) ?>

  <?php if ($flash_success): ?>
    <?= (new \App\View\Components\Alert($flash_success))->render() ?>
  <?php endif; ?>

  <div class="product-detail">
    <div class="product-img">
      <?php product_img($product->image ?? '', $product->name) ?>
    </div>

    <div class="product-meta">
      <?php if ($product->cat_name): ?>
        <div>
          <a href="/category/<?= h($product->cat_slug) ?>"
             style="color:var(--accent);font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;">
            <?= h($product->cat_name) ?>
          </a>
        </div>
      <?php endif; ?>

      <h1 class="product-title"><?= h($product->name) ?></h1>
      <div class="product-price" id="display-price"><?= money($product->price) ?></div>

      <?php if (!empty($product->tiers)): ?>
        <div class="quantity-discounts" style="margin: 1rem 0; padding: 1rem; background: var(--sand); border-radius: var(--radius); border: 1px solid var(--line);">
          <h4 style="margin: 0 0 0.5rem 0; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink-2);">Quantity Discounts</h4>
          <table style="width: 100%; font-size: 0.85rem; border-collapse: collapse;">
            <thead>
              <tr style="text-align: left; border-bottom: 1px solid rgba(0,0,0,0.05);">
                <th style="padding: 0.25rem 0;">Qty</th>
                <th style="padding: 0.25rem 0;">Price Each</th>
                <th style="padding: 0.25rem 0; text-align: right;">Savings</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($product->tiers as $tier): ?>
                <tr class="tier-row" data-discount="<?= $tier['discount'] ?>">
                  <td style="padding: 0.4rem 0;"><?= $tier['min_qty'] ?>+</td>
                  <td style="padding: 0.4rem 0;" class="tier-price"><?= money($product->price - $tier['discount']) ?></td>
                  <td style="padding: 0.4rem 0; text-align: right; color: var(--success); font-weight: 600;">Save <?= money($tier['discount']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <?php if ($promo = get_active_promotion($product)): ?>
        <div class="promo-callout">
          <div class="promo-icon">🏷️</div>
          <div class="promo-text">
            <h4><?= h($promo->name) ?></h4>
            <?php if ($promo->description || $promo->min_order_amount > 0 || $promo->type === 'buy_x_get_y'): ?>
              <p>
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

            <?php if ($promo->code): ?>
              <div class="promo-code-box">
                <span class="promo-code-label">Use code:</span>
                <span class="promo-code-value"><?= h($promo->code) ?></span>
              </div>
              <a href="/promotion/<?= h($promo->code) ?>" class="promo-link">View all qualifying products →</a>
            <?php endif; ?>
          </div>
          <div class="promo-badge">
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
        <p class="product-desc"><?= nl2br(h($product->description)) ?></p>
      <?php endif; ?>

      <div id="stock-status">
        <?php 
          $availableStock = $product->getAvailableStock();
          if ($product->is_virtual):
        ?>
          <?= (new \App\View\Components\StatusBadge('✓ In Stock', 'badge-success'))->render() ?>
        <?php elseif ($availableStock > settings()->low_stock_threshold): 
        ?>
          <?= (new \App\View\Components\StatusBadge('✓ In Stock', 'badge-success'))->render() ?>
        <?php elseif ($availableStock > 0): ?>
          <?= (new \App\View\Components\StatusBadge('⚠ Only ' . $availableStock . ' left', 'badge-warning'))->render() ?>
        <?php else: ?>
          <?= (new \App\View\Components\StatusBadge('✗ Out of Stock', 'badge-danger'))->render() ?>
        <?php endif; ?>
      </div>

      <?php if ($product->is_bundle && !empty($product->bundle_items)): ?>
        <div class="bundle-components-section" style="margin: 1.5rem 0; padding: 1.25rem; border: 1px solid var(--line); border-radius: var(--radius); background: var(--bg-card); box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
          <h4 style="margin-top: 0; margin-bottom: 1rem; color: var(--accent); font-family: var(--font-display); font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
            Included in this Bundle
          </h4>
          <div class="bundle-items-list" style="display: flex; flex-direction: column; gap: 0.75rem;">
            <?php foreach ($product->bundle_items as $item): ?>
              <div class="bundle-component-item" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; padding-bottom: 0.75rem; border-bottom: 1px solid rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <span style="font-weight: 600; color: var(--accent-2); background: var(--sand); padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">
                    <?= (int)$item['bundle_qty'] ?>×
                  </span>
                  <a href="/product/<?= h($item['slug']) ?>" style="font-weight: 500; text-decoration: none; color: var(--ink);">
                    <?= h($item['name']) ?>
                  </a>
                </div>
                <div style="color: var(--ink-2); font-size: 0.85rem;">
                  <?= money($item['price']) ?> each
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div style="margin-top: 1rem; font-size: 0.8rem; color: var(--ink-2); font-style: italic; text-align: right;">
            Total bundle value: <?= money(array_reduce($product->bundle_items, fn($sum, $i) => $sum + ($i['price'] * $i['bundle_qty']), 0)) ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($product->variants)): ?>
        <div class="form-group" style="margin: 1.5rem 0;">
          <label for="variant-select"><strong>Option</strong></label>
          <select id="variant-select" class="form-control" style="max-width:300px;"
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
        <div id="cart-message"></div>
        <form method="POST" id="add-to-cart-form">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= $product->id ?>">
            <input type="hidden" name="variant_id" id="selected-variant-id" value="">
            <input type="hidden" name="slug" value="<?= h($product->slug) ?>">

          <?php if ($product->is_virtual && $product->virtual_type === 'giftcard'): ?>
            <div class="gift-card-recipient-section" style="margin: 1.5rem 0; padding: 1.25rem; border: 1px solid var(--line); border-radius: var(--radius); background: var(--bg-card); box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
              <h4 style="margin-top: 0; margin-bottom: 1rem; color: var(--accent); font-family: var(--font-display); font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                🎁 Gift Card Recipient Details
              </h4>
              
              <div class="form-group" style="margin-bottom: 1rem;">
                <label for="recipient_email" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                  Recipient Email <span style="color: var(--accent-2);">*</span>
                </label>
                <input type="email" id="recipient_email" name="recipient_email" class="form-control" style="width: 100%;" placeholder="friend@example.com" required>
              </div>

              <div class="form-group" style="margin-bottom: 1rem;">
                <label for="sender_name" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                  Sender Name (optional)
                </label>
                <input type="text" id="sender_name" name="sender_name" class="form-control" style="width: 100%;" placeholder="Your Name">
              </div>

              <div class="form-group" style="margin-bottom: 0;">
                <label for="message" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                  Personal Message (optional)
                </label>
                <textarea id="message" name="message" class="form-control" style="width: 100%; height: 80px; resize: none;" placeholder="Happy Birthday! Hope you enjoy this gift card!"></textarea>
              </div>
            </div>
          <?php endif; ?>

          <div class="qty-row">
            <div class="form-group" style="margin:0">
              <label for="qty">Quantity</label>
              <input type="number" id="qty" name="qty" class="form-control qty-input"
                     value="1" min="1" max="<?= $product->is_virtual ? 999999 : $availableStock ?>">
            </div>
            <button type="submit" id="add-to-cart-btn" name="add_to_cart" class="btn btn-primary" style="padding:.65rem 1.8rem;" <?= $product->force_variant && !empty($product->variants) ? 'disabled' : '' ?>>
              Add to Cart
            </button>
          </div>
        </form>

        <div style="margin-top: 1rem;">
          <?php if (!$is_logged_in): ?>
            <p style="font-size: 0.85rem; color: var(--ink-2);">
              <a href="/login">Login</a> to add this item to your wishlist.
            </p>
          <?php elseif ($is_in_wishlist): ?>
            <form action="/wishlist/remove/<?= $product->id ?>" method="post">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-outline btn-block">
                ❤️ Remove from Wishlist
              </button>
            </form>
          <?php else: ?>
            <form action="/wishlist/add/<?= $product->id ?>" method="post">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-outline btn-block">
                ♡ Add to Wishlist
              </button>
            </form>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <section style="margin-top: 4rem; padding-top: 2rem; border-top: 1px solid var(--line);">
    <div style="display:grid;grid-template-columns: 1fr 400px; gap: 4rem; align-items: start;">
      <div>
        <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Customer Reviews</h2>
        <?php if ($reviews): ?>
          <?php foreach ($reviews as $r): ?>
            <div style="margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--line);">
              <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <div style="font-weight: 600;"><?= h($r->user_name) ?></div>
                <div style="font-size: 0.85rem; color: var(--ink-2);"><?= date('d M Y', strtotime($r->created_at)) ?></div>
              </div>
              <div style="font-size: 0.85rem; margin-bottom: 0.75rem;">
                <?= new \App\View\Components\StarRating((float)$r->rating) ?>
              </div>
              <div style="line-height: 1.6;"><?= nl2br(h($r->comment)) ?></div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color: var(--ink-2); font-style: italic;">No reviews yet. Be the first to review this product!</p>
        <?php endif; ?>
      </div>

      <div class="card" style="position: sticky; top: 2rem;">
        <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.2rem;">Write a Review</h3>
        <?php if ($is_logged_in): ?>
          <?php if (isset($flash_error)): ?>
            <?= (new \App\View\Components\Alert($flash_error, 'danger'))->render() ?>
          <?php endif; ?>
          <form action="/product/<?= h($product->slug) ?>/review" method="post">
            <?= csrf_field() ?>
            <div class="form-group">
              <label for="rating"><strong>Rating</strong></label>
              <select name="rating" id="rating" class="form-control" required>
                <option value="5">5 Stars - Excellent</option>
                <option value="4">4 Stars - Very Good</option>
                <option value="3">3 Stars - Good</option>
                <option value="2">2 Stars - Poor</option>
                <option value="1">1 Star - Terrible</option>
              </select>
            </div>
            <div class="form-group">
              <label for="comment"><strong>Comment</strong></label>
              <textarea name="comment" id="comment" class="form-control" rows="5" placeholder="Share your experience with this product..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Submit Review</button>
            <p style="font-size: 0.75rem; color: var(--ink-2); margin-top: 1rem; text-align: center;">
              Your review will be public after it has been approved by our team.
            </p>
          </form>
        <?php else: ?>
          <div style="text-align: center; padding: 1rem;">
            <p>Please log in to share your thoughts.</p>
            <a href="/login?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-outline btn-sm">Login to Review</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if ($related_products): ?>
    <section>
      <h2 class="page-title" style="font-size:1.4rem;">Related Products</h2>
      <div class="product-grid">
        <?php foreach ($related_products as $r): ?>
          <?= (new \App\View\Components\ProductCard($r))->render() ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($recently_viewed)): ?>
    <section style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #eee;">
      <h2 class="page-title" style="font-size:1.2rem; color: var(--ink-2);">Recently Viewed</h2>
      <div class="product-grid" style="grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem;">
        <?php foreach ($recently_viewed as $r): ?>
          <?= (new \App\View\Components\ProductCard(
              $r, 
              false, 
              'font-size: 0.85rem; min-height: auto;', 
              'aspect-ratio: 4/3; height: auto; min-height: auto;',
              'width: 100%; height: 100%; object-fit: cover;'
          ))->render() ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

</div>
