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
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1rem;">
      <p style="color:var(--ink-2);font-size:.85rem;margin:0;">
        <?= $total_products ?> result<?= $total_products != 1 ? 's' : '' ?>
      </p>
      <form method="get" action="/search" style="display:flex;gap:.5rem;align-items:center;">
        <input type="hidden" name="q" value="<?= h($query) ?>">
        <select name="sort" onchange="this.form.submit()" style="padding:.3rem .5rem;border:1px solid var(--border);border-radius:4px;font-size:.85rem;">
          <option value="name"       <?= $sort === 'name'       ? 'selected' : '' ?>>Name</option>
          <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Price: Low to High</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
        </select>
        <select name="per_page" onchange="this.form.submit()" style="padding:.3rem .5rem;border:1px solid var(--border);border-radius:4px;font-size:.85rem;">
          <option value="12"  <?= $per_page_param === '12'  ? 'selected' : '' ?>>12 per page</option>
          <option value="24"  <?= $per_page_param === '24'  ? 'selected' : '' ?>>24 per page</option>
          <option value="all" <?= $per_page_param === 'all' ? 'selected' : '' ?>>All</option>
        </select>
      </form>
    </div>
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

    <?php if ($total_pages > 1): ?>
      <div style="display:flex;gap:.5rem;justify-content:center;margin-top:2rem;">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="?q=<?= urlencode($query) ?>&sort=<?= h($sort) ?>&per_page=<?= h($per_page_param) ?>&page=<?= $i ?>"
             class="btn <?= $i === $current_page ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>
