<?php
/** @var int $currentPage */
/** @var int $totalPages */
/** @var string $baseUrl */
/** @var array $queryParams */

if ($totalPages <= 1) {
    return;
}
?>
<div class="pagination" style="display:flex;gap:.5rem;justify-content:center;margin-top:2rem;">
  <?php for ($i = 1; $i <= $totalPages; $i++): 
    $params = $queryParams;
    $params['page'] = $i;
    $url = $baseUrl . '?' . http_build_query($params);
  ?>
    <a href="<?= h($url) ?>"
       data-page="<?= $i ?>"
       class="btn <?= $i === $currentPage ? 'btn-primary' : 'btn-outline' ?> btn-sm">
      <?= $i ?>
    </a>
  <?php endfor; ?>
</div>
