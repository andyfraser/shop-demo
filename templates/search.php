<?php // templates/search.php ?>
<div class="container">
  <h1 class="page-title">
    <?= $query ? 'Results for "' . h($query) . '"' : 'Search' ?>
  </h1>

  <?php if ($query && !$products): ?>
    <div class="empty-state">
      <div class="icon">🔍</div>
      <h3>No results found</h3>
      <p>Try different keywords or browse by category.</p>
    </div>
  <?php elseif ($products): ?>
    <p style="color:var(--ink-2);font-size:.85rem;margin-bottom:1rem;">
      <?= count($products) ?> result<?= count($products) != 1 ? 's' : '' ?>
    </p>
    <div class="product-grid">
      <?php foreach ($products as $p): ?>
        <a href="/product?slug=<?= h($p['slug']) ?>" class="product-card">
          <div class="img-wrap">
            <?php product_img($p['image'] ?? '', $p['name']) ?>
          </div>
          <div class="card-body">
            <div class="card-cat"><?= h($p['cat_name'] ?? '') ?></div>
            <div class="card-name"><?= h($p['name']) ?></div>
            <div class="card-price"><?= money($p['price']) ?></div>
            <div class="card-actions"><span class="btn btn-primary btn-sm">View</span></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
