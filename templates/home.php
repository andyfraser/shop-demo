<?php // templates/home.php ?>

<div class="hero">
  <div class="hero-inner">
    <h1>Quality goods,<br>curated for you.</h1>
    <p>Browse our thoughtfully selected collection of electronics, clothing, and more.</p>
    <a href="<?= BASE_URL ?>/products" class="btn btn-primary" style="font-size:1rem;padding:.8rem 2rem;">
      Shop All Products
    </a>
  </div>
</div>

<div class="container">

  <section class="section">
    <h2 class="section-title">Shop by Category</h2>
    <div class="cat-grid">
      <?php foreach ($nav_tree as $cat): ?>
        <a href="/category/<?= h($cat->slug) ?>" class="cat-card">
          <div class="cat-icon-wrap">
            <span class="cat-icon"><?= $cat->icon ? h($cat->icon) : '📦' ?></span>
          </div>
          <span class="cat-name"><?= h($cat->name) ?></span>
          <?php if ($cat->children): ?>
            <span class="cat-sub"><?= count($cat->children) ?> subcategories</span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="section">
    <h2 class="section-title">Featured Products</h2>
    <div class="product-grid">
      <?php foreach ($featured_products as $p): ?>
        <?= (new \App\View\Components\ProductCard($p, true))->render() ?>
      <?php endforeach; ?>
    </div>
  </section>

</div>
