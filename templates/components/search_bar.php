<?php
/** @var string $query */
/** @var bool $isMobile */
?>
<?php if ($isMobile): ?>
  <div class="mobile-search-bar">
    <form action="<?= BASE_URL ?>/search" method="GET">
      <div class="search-input-wrapper">
        <input type="text" name="q" placeholder="Search products…" value="<?= h($query) ?>" autocomplete="off">
        <div class="search-suggestions" style="display: none;"></div>
      </div>
      <button type="submit">Search</button>
    </form>
  </div>
<?php else: ?>
  <form class="header-search" action="<?= BASE_URL ?>/search" method="GET">
    <div class="search-input-wrapper">
      <input type="text" name="q" placeholder="Search products…" value="<?= h($query) ?>" autocomplete="off">
      <div class="search-suggestions" style="display: none;"></div>
    </div>
    <button type="submit">Search</button>
  </form>
<?php endif; ?>
