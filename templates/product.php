<?php // templates/product.php ?>

<div class="container">

  <div class="breadcrumb">
    <a href="index.php">Home</a>
    <?php foreach ($breadcrumb as $c): ?>
      <span class="sep">›</span>
      <a href="category.php?slug=<?= h($c['slug']) ?>"><?= h($c['name']) ?></a>
    <?php endforeach; ?>
    <span class="sep">›</span>
    <span><?= h($product['name']) ?></span>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert alert-success"><?= h($flash_success) ?></div>
  <?php endif; ?>

  <div class="product-detail">
    <div class="product-img">
      <?php product_img($product['image'] ?? '', $product['name']) ?>
    </div>

    <div class="product-meta">
      <?php if ($product['cat_name']): ?>
        <div>
          <a href="category.php?slug=<?= h($product['cat_slug']) ?>"
             style="color:var(--accent);font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;">
            <?= h($product['cat_name']) ?>
          </a>
        </div>
      <?php endif; ?>

      <h1 class="product-title"><?= h($product['name']) ?></h1>
      <div class="product-price"><?= money($product['price']) ?></div>

      <?php if ($product['description']): ?>
        <p class="product-desc"><?= nl2br(h($product['description'])) ?></p>
      <?php endif; ?>

      <div>
        <?php if ($product['stock'] > 10): ?>
          <span class="badge badge-success">✓ In Stock</span>
        <?php elseif ($product['stock'] > 0): ?>
          <span class="badge badge-warning">⚠ Only <?= $product['stock'] ?> left</span>
        <?php else: ?>
          <span class="badge badge-danger">✗ Out of Stock</span>
        <?php endif; ?>
        <span style="margin-left:.5rem;font-size:.875rem;color:var(--ink-2);">
          <?= $product['stock'] ?> units available
        </span>
      </div>

      <?php if ($product['stock'] > 0): ?>
        <form method="POST">
          <div class="qty-row">
            <div class="form-group" style="margin:0">
              <label for="qty">Quantity</label>
              <input type="number" id="qty" name="qty" class="form-control qty-input"
                     value="1" min="1" max="<?= $product['stock'] ?>">
            </div>
            <button type="submit" name="add_to_cart" class="btn btn-primary" style="padding:.65rem 1.8rem;">
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
          <a href="product.php?slug=<?= h($r['slug']) ?>" class="product-card">
            <div class="img-wrap">
              <?php product_img($r['image'] ?? '', $r['name']) ?>
            </div>
            <div class="card-body">
              <div class="card-name"><?= h($r['name']) ?></div>
              <div class="card-price"><?= money($r['price']) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

</div>
