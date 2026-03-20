<?php // templates/home.php ?>

<div class="hero">
  <div class="hero-inner">
    <h1>Quality goods,<br>curated for you.</h1>
    <p>Browse our thoughtfully selected collection of electronics, clothing, and more.</p>
    <a href="index.php" class="btn btn-primary" style="font-size:1rem;padding:.8rem 2rem;">
      Shop All Products
    </a>
  </div>
</div>

<div class="container">

  <section class="section">
    <h2 class="section-title">Shop by Category</h2>
    <div class="cat-grid">
      <?php
      $icons = ['electronics' => '💻', 'clothing' => '👕', 'home-garden' => '🏠'];
      foreach ($nav_tree as $cat):
      ?>
        <a href="category.php?slug=<?= h($cat['slug']) ?>" class="cat-card">
          <div class="cat-icon"><?= $icons[$cat['slug']] ?? '📦' ?></div>
          <?= h($cat['name']) ?>
          <?php if ($cat['children']): ?>
            <div class="cat-sub"><?= count($cat['children']) ?> subcategories</div>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="section">
    <h2 class="section-title">Featured Products</h2>
    <div class="product-grid">
      <?php foreach ($featured_products as $p): ?>
        <a href="product.php?slug=<?= h($p['slug']) ?>" class="product-card">
          <div class="img-wrap">
            <?php if ($p['image_url']): ?>
              <img src="<?= h($p['image_url']) ?>" alt="<?= h($p['name']) ?>" loading="lazy">
            <?php else: ?>
              <div style="height:100%;display:flex;align-items:center;justify-content:center;font-size:3rem;">📦</div>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <div class="card-cat"><?= h($p['cat_name'] ?? 'Uncategorised') ?></div>
            <div class="card-name"><?= h($p['name']) ?></div>
            <div class="card-price"><?= money($p['price']) ?></div>
            <div class="card-actions">
              <span class="btn btn-primary btn-sm">View Product</span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

</div>
