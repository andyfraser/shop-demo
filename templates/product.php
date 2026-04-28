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
          <select id="variant-select" class="form-control" style="max-width:300px;">
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
      <?php endif; ?>
    </div>
  </div>

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
              <?php product_img($r->image ?? '', $r->name) ?>
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
      <h2 class="page-title" style="font-size:1.4rem;">Recently Viewed</h2>
      <div class="product-grid">
        <?php foreach ($recently_viewed as $r): ?>
          <a href="/product/<?= h($r->slug) ?>" class="product-card">
            <div class="img-wrap">
              <?php if ($r->featured): ?>
                <span class="product-badge badge-featured">Featured</span>
              <?php elseif ($r->isNew()): ?>
                <span class="product-badge badge-new">New</span>
              <?php endif; ?>
              <?php product_img($r->image ?? '', $r->name) ?>
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

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const variantSelect = document.getElementById('variant-select');
    if (!variantSelect) return;

    const displayPrice = document.getElementById('display-price');
    const stockStatus = document.getElementById('stock-status');
    const variantIdInput = document.getElementById('selected-variant-id');
    const qtyInput = document.getElementById('qty');
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    const lowStockThreshold = <?= settings()->low_stock_threshold ?>;

    function formatMoney(amount) {
        return '£' + parseFloat(amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    variantSelect.addEventListener('change', function() {
        const option = variantSelect.options[variantSelect.selectedIndex];
        const price = option.dataset.price;
        const stock = parseInt(option.dataset.stock);
        const vid = option.value;

        // Update price
        displayPrice.textContent = formatMoney(price);

        // Update hidden variant ID
        variantIdInput.value = vid;

        // Update stock status
        let badgeHtml = '';
        if (stock > lowStockThreshold) {
            badgeHtml = '<span class="badge badge-success">✓ In Stock</span>';
            addToCartBtn.disabled = false;
        } else if (stock > 0) {
            badgeHtml = '<span class="badge badge-warning">⚠ Only ' + stock + ' left</span>';
            addToCartBtn.disabled = false;
        } else {
            badgeHtml = '<span class="badge badge-danger">✗ Out of Stock</span>';
            addToCartBtn.disabled = true;
        }
        stockStatus.innerHTML = badgeHtml;

        // Update quantity max
        qtyInput.max = stock;
        if (parseInt(qtyInput.value) > stock) {
            qtyInput.value = stock || 1;
        }
    });
});
</script>
