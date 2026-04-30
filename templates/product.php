<?php // templates/product.php ?>

<div class="container">

  <div class="breadcrumb">
    <a href="<?= BASE_URL ?>/">Home</a>
    <?php foreach ($breadcrumb as $c): ?>
      <span class="sep">›</span>
      <a href="/category/<?= h($c->slug) ?>"><?= h($c->name) ?></a>
    <?php endforeach; ?>
    <span class="sep">›</span>
    <span><?= h($product->name) ?></span>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert alert-success"><?= h($flash_success) ?></div>
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

      <?php if ($product->description): ?>
        <p class="product-desc"><?= nl2br(h($product->description)) ?></p>
      <?php endif; ?>

      <div id="stock-status">
        <?php if ($product->stock > settings()->low_stock_threshold): ?>
          <span class="badge badge-success">✓ In Stock</span>
        <?php elseif ($product->stock > 0): ?>
          <span class="badge badge-warning">⚠ Only <?= $product->stock ?> left</span>
        <?php else: ?>
          <span class="badge badge-danger">✗ Out of Stock</span>
        <?php endif; ?>
      </div>

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
                      data-stock="<?= $product->stock ?>">
                Default (<?= money($product->price) ?>)
              </option>
            <?php endif; ?>
            <?php foreach ($product->variants as $v): ?>
              <option value="<?= $v->id ?>" 
                      data-price="<?= $v->getEffectivePrice($product->price) ?>" 
                      data-stock="<?= $v->stock ?>">
                <?= h($v->name) ?> (<?= $v->formattedPrice($product->price) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <?php if ($product->stock > 0 || !empty($product->variants)): ?>
        <div id="cart-message"></div>
        <form method="POST" id="add-to-cart-form">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= $product->id ?>">
            <input type="hidden" name="variant_id" id="selected-variant-id" value="">
            <input type="hidden" name="slug" value="<?= h($product->slug) ?>">
          <div class="qty-row">
            <div class="form-group" style="margin:0">
              <label for="qty">Quantity</label>
              <input type="number" id="qty" name="qty" class="form-control qty-input"
                     value="1" min="1" max="<?= $product->stock ?>">
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
              <div style="color:var(--gold); font-size: 0.85rem; margin-bottom: 0.75rem;">
                <?= $r->getStarRating() ?>
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
            <div class="alert alert-danger" style="margin-bottom: 1rem;"><?= h($flash_error) ?></div>
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
          <a href="/product/<?= h($r->slug) ?>" class="product-card">
            <div class="img-wrap">
              <?php if ($r->featured): ?>
                <span class="product-badge badge-featured">Featured</span>
              <?php elseif ($r->isNew()): ?>
                <span class="product-badge badge-new">New</span>
              <?php endif; ?>
              <?php product_img($r->image ?? '', $r->name, '', '', 'thumb') ?>
            </div>
            <div class="card-body">
              <div class="card-name"><?= h($r->name) ?></div>
              <div class="card-price"><?= money($r->price) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($recently_viewed)): ?>
    <section style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #eee;">
      <h2 class="page-title" style="font-size:1.2rem; color: var(--ink-2);">Recently Viewed</h2>
      <div class="product-grid" style="grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem;">
        <?php foreach ($recently_viewed as $r): ?>
          <a href="/product/<?= h($r->slug) ?>" class="product-card" style="font-size: 0.85rem; min-height: auto;">
            <div class="img-wrap" style="aspect-ratio: 4/3; height: auto; min-height: auto;">
              <?php if ($r->featured): ?>
                <span class="product-badge badge-featured" style="font-size: 0.65rem; padding: 1px 4px;">Featured</span>
              <?php elseif ($r->isNew()): ?>
                <span class="product-badge badge-new" style="font-size: 0.65rem; padding: 1px 4px;">New</span>
              <?php endif; ?>
              <?php product_img($r->image ?? '', $r->name, '', 'width: 100%; height: 100%; object-fit: cover;') ?>
            </div>
            <div class="card-body" style="padding: 0.5rem;">
              <div class="card-name" style="margin-bottom: 0.15rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= h($r->name) ?></div>
              <div class="card-price" style="font-weight: 600; font-size: 0.85rem;"><?= money($r->price) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

</div>
