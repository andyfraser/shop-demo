<?php
/** @var float $rating */
/** @var int $fullStars */
/** @var bool $halfStar */
/** @var int $emptyStars */
/** @var bool $showNumeric */
/** @var string $style */

$styleAttr = $style ? ' style="' . h($style) . '"' : '';
?>
<div class="star-rating"<?= $styleAttr ?> title="<?= number_format($rating, 1) ?> out of 5 stars" style="display: inline-flex; align-items: center; gap: 0.25rem;">
  <span class="stars-gold" style="color: var(--gold); font-size: inherit;">
    <?= str_repeat('★', $fullStars) . ($halfStar ? '½' : '') . str_repeat('☆', $emptyStars) ?>
  </span>
  <?php if ($showNumeric): ?>
    <span class="rating-numeric" style="color: var(--ink-2); font-size: 0.8rem; margin-left: 0.25rem;">
      (<?= number_format($rating, 1) ?>/5)
    </span>
  <?php endif; ?>
</div>
