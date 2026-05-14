<?php // templates/products.php ?>

<div class="container">

  <h1 class="page-title"><?= h($page_title) ?></h1>

  <?php if (isset($promotion)): ?>
    <div class="promo-header" style="background:var(--bg-2); padding:1.5rem; border-radius:var(--radius); margin-bottom:2rem; border:1px solid var(--border);">
      <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1rem;">
        <span style="font-size:2rem;">🎟️</span>
        <div>
          <h2 style="margin:0; font-family:var(--font-display);"><?= h($promotion->name) ?></h2>
          <?php if ($promotion->code): ?>
            <div class="promo-code-box" style="margin-top:0.25rem;">
              <span class="promo-code-label">Use code:</span>
              <span class="promo-code-value"><?= h($promotion->code) ?></span>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($promotion->description): ?>
        <p style="margin:0; color:var(--ink-2); font-size:0.95rem;"><?= nl2br(h($promotion->description)) ?></p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="storefront-layout-container">
    <form id="filters-form" method="get" action="">
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
                <?php 
                  $total_vals = count($attr['values']);
                  $initial_vals = array_slice($attr['values'], 0, 4);
                  $extra_vals = array_slice($attr['values'], 4);
                ?>

                <?php foreach ($initial_vals as $val): ?>
                  <label>
                    <input type="checkbox" name="attr[]" value="<?= $val['id'] ?>" 
                      <?= in_array($val['id'], $active_filters['attributes']) ? 'checked' : '' ?>>
                    <?= h($val['name']) ?> <span class="count">(<?= $val['count'] ?>)</span>
                  </label>
                <?php endforeach; ?>

                <?php if ($extra_vals): ?>
                  <div class="filter-extra">
                    <?php foreach ($extra_vals as $val): ?>
                      <label>
                        <input type="checkbox" name="attr[]" value="<?= $val['id'] ?>" 
                          <?= in_array($val['id'], $active_filters['attributes']) ? 'checked' : '' ?>>
                        <?= h($val['name']) ?> <span class="count">(<?= $val['count'] ?>)</span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                  <button type="button" class="btn-toggle-filters">Show more</button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
          
          <noscript>
              <button type="submit" class="btn btn-primary btn-block btn-sm">Apply Filters</button>
          </noscript>
          <a href="<?= parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?>" class="btn btn-outline btn-block btn-sm" style="margin-top:.5rem;text-align:center;display:block;">Clear All</a>
        </aside>

        <div class="products-column" id="products-list">
          <?php require __DIR__ . '/partials/product_list.php'; ?>
        </div>
      </div>
    </form>
  </div>

</div>
