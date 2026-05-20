<?php // templates/wishlist.php ?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <h1 class="page-title" style="margin-bottom: 0;">My Wishlist</h1>
        
        <?php if (!empty($wishlist)): ?>
        <div class="wishlist-settings" style="background: var(--bg-soft); padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid var(--line); display: flex; flex-direction: row-reverse; align-items: center; gap: 1.5rem; min-height: 56px;">
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
                <span id="privacy-label" style="font-weight: 500; font-size: 0.9rem; min-width: 60px; text-align: right;">
                    <?= $settings['is_public'] ? 'Public' : 'Private' ?>
                </span>
                <label class="switch" style="position: relative; display: inline-block; width: 44px; height: 24px; margin-bottom: 0;">
                    <input type="checkbox" id="privacy-toggle" <?= $settings['is_public'] ? 'checked' : '' ?> style="opacity: 0; width: 0; height: 0;">
                    <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px;"></span>
                </label>
            </div>
            
            <div id="share-link-section" style="display: <?= $settings['is_public'] ? 'flex' : 'none' ?>; align-items: center; gap: 0.5rem;">
                <input type="text" id="share-url" value="<?= h($share_url ?? '') ?>" readonly style="background: white; border: 1px solid var(--line); padding: 0.4rem 0.8rem; border-radius: 4px; font-size: 0.85rem; width: 250px;">
                <button type="button" onclick="copyShareUrl()" class="btn btn-primary btn-sm">Copy</button>
            </div>
        </div>
        <?php endif; ?>
    </div>

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
