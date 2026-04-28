<?php // templates/partials/product_list.php ?>
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1rem;">
  <p style="color:var(--ink-2);font-size:.85rem;margin:0;">
    <?= $total_products ?> product<?= $total_products != 1 ? 's' : '' ?>
  </p>
  <div style="display:flex;gap:.5rem;align-items:center;">
    <select name="sort" onchange="this.form.dispatchEvent(new Event('change', {bubbles: true}))" style="padding:.3rem .5rem;border:1px solid var(--border);border-radius:4px;font-size:.85rem;">
      <option value="name"       <?= $sort === 'name'       ? 'selected' : '' ?>>Name</option>
      <option value="featured"   <?= $sort === 'featured'   ? 'selected' : '' ?>>Featured</option>
      <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Price: Low to High</option>
      <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
    </select>
    <select name="per_page" onchange="this.form.dispatchEvent(new Event('change', {bubbles: true}))" style="padding:.3rem .5rem;border:1px solid var(--border);border-radius:4px;font-size:.85rem;">
      <option value="12"  <?= $per_page_param === '12'  ? 'selected' : '' ?>>12 per page</option>
      <option value="24"  <?= $per_page_param === '24'  ? 'selected' : '' ?>>24 per page</option>
      <option value="all" <?= $per_page_param === 'all' ? 'selected' : '' ?>>All</option>
    </select>
  </div>
</div>

<div class="product-grid">
  <?php foreach ($products as $p): ?>
    <a href="/product/<?= h($p->slug) ?>" class="product-card">
      <div class="img-wrap">
        <?php if ($p->featured): ?>
          <span class="product-badge badge-featured">Featured</span>
        <?php elseif ($p->isNew()): ?>
          <span class="product-badge badge-new">New</span>
        <?php endif; ?>
        <?php product_img($p->image ?? '', $p->name) ?>
      </div>
      <div class="card-body">
        <?php if (isset($category) && $p->cat_name !== $category->name): ?>
          <div class="card-cat"><?= h($p->cat_name) ?></div>
        <?php endif; ?>
        <div class="card-name"><?= h($p->name) ?></div>
        <div class="card-price"><?= money($p->price) ?></div>
        <div class="card-actions">
          <span class="btn btn-primary btn-sm">View</span>
        </div>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<?php if ($total_pages > 1): ?>
  <div class="pagination" style="display:flex;gap:.5rem;justify-content:center;margin-top:2rem;">
    <?php 
      $base_url = $_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI'];
      $base_url = explode('?', $base_url)[0];
      $query_params = $_GET;
      unset($query_params['ajax']);
    ?>
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <?php 
        $query_params['page'] = $i;
        $url = $base_url . '?' . http_build_query($query_params);
      ?>
      <a href="<?= h($url) ?>"
         data-page="<?= $i ?>"
         class="btn <?= $i === $current_page ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<?php if (empty($products)): ?>
    <div class="empty-state">
      <div class="icon">📦</div>
      <h3>No products found</h3>
      <p>Try adjusting your filters.</p>
    </div>
<?php endif; ?>
