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
        <?php require __DIR__ . '/partials/filters.php'; ?>

        <div class="products-column" id="products-list">
          <?php require __DIR__ . '/partials/product_list.php'; ?>
        </div>
      </div>
    </form>
  </div>

</div>
