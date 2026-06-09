<?php
/** @var \App\Models\Product $product */
/** @var bool $showCategory */
/** @var string $style */
/** @var string $imgWrapStyle */
/** @var string $imgStyle */

$p = $product;
$slug = h($p->slug);
$name = h($p->name);
$price = money($p->price);
$catName = h($p->cat_name ?? '');
$styleAttr = $style ? ' style="' . h($style) . '"' : '';
$imgWrapStyleAttr = $imgWrapStyle ? ' style="' . h($imgWrapStyle) . '"' : '';
?>
<a href="/product/<?= $slug ?>" class="product-card"<?= $styleAttr ?>>
  <div class="img-wrap"<?= $imgWrapStyleAttr ?>>
    <?php 
      if (!promotion_badge($p)):
        if ($p->featured): 
    ?>
      <span class="product-badge badge-featured">Featured</span>
    <?php elseif ($p->isNew()): ?>
      <span class="product-badge badge-new">New</span>
    <?php endif; endif; ?>
    <?php product_img($p->image ?? '', $p->name, '', $imgStyle, 'thumb', '(max-width: 480px) 100vw, (max-width: 800px) 50vw, 300px') ?>
    <button type="button" class="quickview-btn" data-slug="<?= $slug ?>" aria-label="Quick View" title="Quick View">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="quickview-icon" width="18" height="18">
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
        <circle cx="12" cy="12" r="3"></circle>
      </svg>
    </button>
  </div>
  <div class="card-body">
    <?php if ($showCategory && $catName): ?>
      <div class="card-cat"><?= $catName ?></div>
    <?php endif; ?>
    <div class="card-name"><?= $name ?></div>
    <div class="card-price"><?= $price ?></div>
    <div class="card-actions">
      <span class="btn btn-primary btn-sm">View</span>
    </div>
  </div>
</a>
