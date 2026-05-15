<?php
namespace App\View\Components;

use App\Core\ViewComponent;
use App\Models\Product;

class ProductCard implements ViewComponent {
    public function __construct(
        private Product $product,
        private bool $showCategory = false,
        private string $style = '',
        private string $imgWrapStyle = '',
        private string $imgStyle = ''
    ) {}

    public function render(): string {
        $p = $this->product;
        $slug = h($p->slug);
        $name = h($p->name);
        $price = money($p->price);
        $catName = h($p->cat_name ?? '');
        $styleAttr = $this->style ? ' style="' . h($this->style) . '"' : '';
        $imgWrapStyleAttr = $this->imgWrapStyle ? ' style="' . h($this->imgWrapStyle) . '"' : '';
        
        ob_start();
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
            <?php product_img($p->image ?? '', $p->name, '', $this->imgStyle, 'thumb') ?>
          </div>
          <div class="card-body">
            <?php if ($this->showCategory && $catName): ?>
              <div class="card-cat"><?= $catName ?></div>
            <?php endif; ?>
            <div class="card-name"><?= $name ?></div>
            <div class="card-price"><?= $price ?></div>
            <div class="card-actions">
              <span class="btn btn-primary btn-sm">View</span>
            </div>
          </div>
        </a>
        <?php
        return ob_get_clean();
    }
}
