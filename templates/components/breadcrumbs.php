<?php
/** @var array $items */
?>
<div class="breadcrumb">
  <?php foreach ($items as $index => $item): ?>
    <?php if ($index > 0): ?>
      <span class="sep">›</span>
    <?php endif; ?>
    
    <?php if ($item['url'] !== null && $index < count($items) - 1): ?>
      <a href="<?= h($item['url']) ?>"><?= h($item['label']) ?></a>
    <?php else: ?>
      <span><?= h($item['label']) ?></span>
    <?php endif; ?>
  <?php endforeach; ?>
</div>
