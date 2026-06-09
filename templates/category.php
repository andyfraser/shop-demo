<?php // templates/category.php ?>

<div class="container">

  <?php
    $crumbs = ['Home' => BASE_URL . '/'];
    foreach ($breadcrumb as $c) {
        $crumbs[] = [
            'label' => $c->name,
            'url' => $c->id === $category->id ? null : "/category/{$c->slug}"
        ];
    }
  ?>
  <?= new \App\View\Components\Breadcrumbs($crumbs) ?>

  <h1 class="page-title"><?= h($category->name) ?></h1>

  <?php if ($category->description): ?>
    <p style="color:var(--ink-2);margin-bottom:1.5rem;"><?= h($category->description) ?></p>
  <?php endif; ?>

  <?php if (!empty($subcategories)): ?>
    <div class="subcategory-buttons" style="display:flex;flex-wrap:wrap;gap:.75rem;margin-bottom:2rem;">
      <?php foreach ($subcategories as $s): ?>
        <a href="/category/<?= h($s->slug) ?>" class="btn btn-outline btn-sm">
          <?= h($s->name) ?>
        </a>
      <?php endforeach; ?>
    </div>
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
