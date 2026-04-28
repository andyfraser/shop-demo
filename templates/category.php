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

  <div class="storefront-layout-container">
    <form id="filters-form" method="get" action="/category/<?= h($category->slug) ?>">
      <div class="storefront-layout">
        <aside class="filters-sidebar">
          <div class="filter-group">
            <h4>Price Range</h4>
            <div class="price-inputs">
              <input type="number" name="price_min" placeholder="Min" value="<?= h($active_filters['price_min'] ?? '') ?>" step="0.01">
              <span>-</span>
              <input type="number" name="price_max" placeholder="Max" value="<?= h($active_filters['price_max'] ?? '') ?>" step="0.01">
            </div>
          </div>

          <?php foreach ($available_filters['attributes'] as $attr): ?>
            <div class="filter-group">
              <h4><?= h($attr['name']) ?></h4>
              <div class="filter-options">
                <?php foreach ($attr['values'] as $val): ?>
                  <label>
                    <input type="checkbox" name="attr[]" value="<?= $val['id'] ?>" 
                      <?= in_array($val['id'], $active_filters['attributes']) ? 'checked' : '' ?>>
                    <?= h($val['name']) ?> <span class="count">(<?= $val['count'] ?>)</span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
          
          <noscript>
              <button type="submit" class="btn btn-primary btn-block btn-sm">Apply Filters</button>
          </noscript>
          <a href="/category/<?= h($category->slug) ?>" class="btn btn-outline btn-block btn-sm" style="margin-top:.5rem;text-align:center;display:block;">Clear All</a>
        </aside>

        <div class="products-column" id="products-list">
          <?php require __DIR__ . '/partials/product_list.php'; ?>
        </div>
      </div>
    </form>
  </div>

</div>
