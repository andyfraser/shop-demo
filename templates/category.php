<?php // templates/category.php ?>

<div class="container">

  <div class="breadcrumb">
    <a href="index.php">Home</a>
    <?php foreach ($breadcrumb as $c): ?>
      <span class="sep">›</span>
      <?php if ($c['id'] === $category['id']): ?>
        <span><?= h($c['name']) ?></span>
      <?php else: ?>
        <a href="category.php?slug=<?= h($c['slug']) ?>"><?= h($c['name']) ?></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <h1 class="page-title"><?= h($category['name']) ?></h1>

  <?php if ($category['description']): ?>
    <p style="color:var(--ink-2);margin-bottom:1.5rem;"><?= h($category['description']) ?></p>
  <?php endif; ?>

  <?php if ($subcategories): ?>
    <div style="display:flex;flex-wrap:wrap;gap:.75rem;margin-bottom:2rem;">
      <?php foreach ($subcategories as $s): ?>
        <a href="category.php?slug=<?= h($s['slug']) ?>" class="btn btn-outline btn-sm">
          <?= h($s['name']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($products): ?>
    <p style="color:var(--ink-2);font-size:.85rem;margin-bottom:1rem;">
      <?= $total_products ?> product<?= $total_products != 1 ? 's' : '' ?>
    </p>
    <div class="product-grid">
      <?php foreach ($products as $p): ?>
        <a href="product.php?slug=<?= h($p['slug']) ?>" class="product-card">
          <div class="img-wrap">
            <?php product_img($p['image_url'] ?? '', $p['name']) ?>
          </div>
          <div class="card-body">
            <?php if ($p['cat_name'] !== $category['name']): ?>
              <div class="card-cat"><?= h($p['cat_name']) ?></div>
            <?php endif; ?>
            <div class="card-name"><?= h($p['name']) ?></div>
            <div class="card-price"><?= money($p['price']) ?></div>
            <div class="card-actions">
              <span class="btn btn-primary btn-sm">View</span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1): ?>
      <div style="display:flex;gap:.5rem;justify-content:center;margin-top:2rem;">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="?slug=<?= h($category['slug']) ?>&page=<?= $i ?>"
             class="btn <?= $i === $current_page ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <div class="empty-state">
      <div class="icon">📦</div>
      <h3>No products found</h3>
      <p>This category doesn't have any products yet.</p>
    </div>
  <?php endif; ?>

</div>
