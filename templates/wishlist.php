<?php // templates/wishlist.php ?>

<div class="container">
    <h1 class="page-title">My Wishlist</h1>

    <?php if (empty($wishlist)): ?>
        <div class="empty-state">
            <div class="icon">❤️</div>
            <h3>Your wishlist is empty</h3>
            <p>Save items you like to find them easily later.</p>
            <a href="/products" class="btn btn-primary" style="margin-top: 1rem;">Browse Products</a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($wishlist as $p): ?>
                <div class="product-card-wrap" style="position: relative;">
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
                            <div class="card-name"><?= h($p->name) ?></div>
                            <div class="card-price"><?= money($p->price) ?></div>
                            <div class="card-actions">
                                <span class="btn btn-primary btn-sm">View Product</span>
                            </div>
                        </div>
                    </a>
                    
                    <form action="/wishlist/remove/<?= $p->id ?>" method="post" style="position: absolute; top: 10px; right: 10px; z-index: 10;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline" style="background: white; border-radius: 50%; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; color: var(--error); border-color: var(--line); font-size: 1.5rem; line-height: 1;" title="Remove from Wishlist">
                            &times;
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
