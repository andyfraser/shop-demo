<?php // templates/wishlist_shared.php ?>

<div class="container">
    <h1 class="page-title"><?= h($owner_name) ?>'s Wishlist</h1>

    <?php if (empty($wishlist)): ?>
        <div class="empty-state">
            <div class="icon">❤️</div>
            <h3>This wishlist is empty</h3>
            <p>The user hasn't added any products yet.</p>
            <a href="/products" class="btn btn-primary" style="margin-top: 1rem;">Browse Products</a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($wishlist as $p): ?>
                <div class="product-card-wrap">
                    <a href="/product/<?= h($p->slug) ?>" class="product-card">
                        <div class="img-wrap">
                            <?php 
                                if (!promotion_badge($p)):
                                    if ($p->featured): 
                            ?>
                                <span class="product-badge badge-featured">Featured</span>
                            <?php elseif ($p->isNew()): ?>
                                <span class="product-badge badge-new">New</span>
                            <?php endif; endif; ?>
                            <?php product_img($p->image ?? '', $p->name, '', '', 'thumb', '(max-width: 480px) 100vw, (max-width: 800px) 50vw, 300px') ?>
                        </div>
                        <div class="card-body">
                            <div class="card-name"><?= h($p->name) ?></div>
                            <div class="card-price"><?= money($p->price) ?></div>
                            <div class="card-actions">
                                <span class="btn btn-primary btn-sm">View Product</span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
