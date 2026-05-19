<?php // templates/search.php ?>
<div class="container">
  <h1 class="page-title">
    <?= $query ? 'Results for "' . h($query) . '"' : 'Search' ?>
  </h1>

  <div class="storefront-layout-container">
    <form id="filters-form" method="get" action="/search">
      <input type="hidden" name="q" value="<?= h($query) ?>">
      <div class="storefront-layout">
        <?php require __DIR__ . '/partials/filters.php'; ?>

        <div class="products-column" id="products-list">
          <?php require __DIR__ . '/partials/product_list.php'; ?>
        </div>
      </div>
    </form>
  </div>
</div>
