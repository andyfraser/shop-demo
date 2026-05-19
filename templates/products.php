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
        <?php require __DIR__ . '/partials/filters.php'; ?>

        <div class="products-column" id="products-list">
          <?php require __DIR__ . '/partials/product_list.php'; ?>
        </div>
      </div>
    </form>
  </div>

</div>
