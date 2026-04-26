<?php // templates/category.php ?>

<div class="container">

  <div class="breadcrumb">
    <a href="<?= BASE_URL ?>/">Home</a>
    <?php foreach ($breadcrumb as $c): ?>
      <span class="sep">›</span>
      <?php if ($c->id === $category->id): ?>
        <span><?= h($c->name) ?></span>
      <?php else: ?>
        <a href="/category/<?= h($c->slug) ?>"><?= h($c->name) ?></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <h1 class="page-title"><?= h($category->name) ?></h1>

  <?php if ($category->description): ?>
    <p style="color:var(--ink-2);margin-bottom:1.5rem;"><?= h($category->description) ?></p>
  <?php endif; ?>

  <?php if ($subcategories): ?>
    <div style="display:flex;flex-wrap:wrap;gap:.75rem;margin-bottom:2rem;">
      <?php foreach ($subcategories as $s): ?>
        <a href="/category/<?= h($s->slug) ?>" class="btn btn-outline btn-sm">
          <?= h($s->name) ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($products): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1rem;">
      <p style="color:var(--ink-2);font-size:.85rem;margin:0;">
        <?= $total_products ?> product<?= $total_products != 1 ? 's' : '' ?>
      </p>
      <form method="get" action="/category" style="display:flex;gap:.5rem;align-items:center;">
        <input type="hidden" name="slug" value="<?= h($category->slug) ?>">
        <select name="sort" onchange="this.form.submit()" style="padding:.3rem .5rem;border:1px solid var(--border);border-radius:4px;font-size:.85rem;">
          <option value="name"       <?= $sort === 'name'       ? 'selected' : '' ?>>Name</option>
          <option value="featured"   <?= $sort === 'featured'   ? 'selected' : '' ?>>Featured</option>
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
            <?php if ($p->cat_name !== $category->name): ?>
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
      <div style="display:flex;gap:.5rem;justify-content:center;margin-top:2rem;">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="/category/<?= h($category->slug) ?>?sort=<?= h($sort) ?>&per_page=<?= h($per_page_param) ?>&page=<?= $i ?>"
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
