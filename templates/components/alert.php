<?php
/** @var string $message */
/** @var string $type */
/** @var bool $dismissible */

$messageEsc = h($message);
$typeEsc = h($type);
$onclick = $dismissible ? ' onclick="this.remove()"' : '';
?>
<div class="alert alert-<?= $typeEsc ?>"<?= $onclick ?>><?= $messageEsc ?></div>
