<?php
/** @var string $label */
/** @var string $class */
/** @var string $style */

$labelEsc = h($label);
$classEsc = h($class);
$styleAttr = $style ? ' style="' . h($style) . '"' : '';
?>
<span class="badge <?= $classEsc ?>"<?= $styleAttr ?>><?= $labelEsc ?></span>
