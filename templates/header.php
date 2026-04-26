<?php
// templates/layout.php
// Variables expected: $page_title (string), $search_query (string, optional)
// Shared via Renderer: $current_user, $cart_count, $nav_tree
// Uses: h(), csrf_token(), BASE_URL, SITE_NAME, SITE_NAME_PLAIN
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($page_title ?? '') ?> — <?= SITE_NAME_PLAIN ?></title>
<link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/public/images/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/shop.css?v=<?= filemtime(__DIR__ . '/../public/css/shop.css') ?>">
<meta name="csrf-token" content="<?= h(csrf_token()) ?>">
<?php
$_top      = count($nav_tree);
$_children = array_sum(array_map(fn($c) => count($c->children), $nav_tree));
$s = settings();
$mobile_nav_expanded = (
    $_top      <= $s->mobile_nav_max_top &&
    ($_top + $_children) <= $s->mobile_nav_max_combined
);
?>
</head>
<body>

<header class="site-header">
  <div class="header-inner">
    <?php
      $logo_parts = explode('|', SITE_NAME, 2);
      $logo_prefix = $logo_parts[0];
      $logo_suffix = $logo_parts[1] ?? '';
    ?>
    <a href="<?= BASE_URL ?>/" class="logo"><?= h($logo_prefix) ?><?php if ($logo_suffix !== ''): ?><span><?= h($logo_suffix) ?></span><?php endif ?></a>

    <form class="header-search" action="<?= BASE_URL ?>/search" method="GET">
      <input type="text" name="q" placeholder="Search products…"
             value="<?= h($search_query ?? '') ?>">
      <button type="submit">Search</button>
    </form>

    <div class="header-right">
      <nav class="header-actions">
        <?php if ($current_user): ?>
          <a href="<?= BASE_URL ?>/account">👤 <span class="link-text"><?= h($current_user->name) ?></span></a>
          <?php if ($current_user->isAdmin()): ?>
            <a href="<?= BASE_URL ?>/admin">⚙ <span class="link-text">Admin</span></a>
          <?php endif; ?>
          <a href="<?= BASE_URL ?>/logout">🚪 <span class="link-text">Sign out</span></a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/login">🔑 <span class="link-text">Sign in</span></a>
          <a href="<?= BASE_URL ?>/register">📝 <span class="link-text">Register</span></a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/cart" id="cart-link">
          🛒 <span class="link-text">Cart</span>
          <?php if ($cart_count > 0): ?>
            <span class="cart-badge"><?= $cart_count ?></span>
          <?php endif; ?>
        </a>
      </nav>

      <button class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-label="Toggle navigation" data-tooltip="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>

<div class="mobile-search-bar">
  <form action="<?= BASE_URL ?>/search" method="GET">
    <input type="text" name="q" placeholder="Search products…" value="<?= h($search_query ?? '') ?>">
    <button type="submit">Search</button>
  </form>
</div>

<nav class="site-nav<?= $mobile_nav_expanded ? '' : ' nav-collapsed' ?>" id="site-nav">
  <div class="nav-inner">
    <ul>
      <li><a href="<?= BASE_URL ?>/">Home</a></li>
      <?php foreach ($nav_tree as $cat): ?>
        <li class="<?= $cat->children ? 'has-sub' : '' ?>">
          <a href="<?= BASE_URL ?>/category/<?= h($cat->slug) ?>">
            <?= h($cat->name) ?>
            <?php if ($cat->children): ?><span class="arrow">›</span><?php endif; ?>
          </a>
          <?php if ($cat->children): ?>
            <ul class="sub-menu">
              <?php foreach ($cat->children as $sub): ?>
                <li>
                  <a href="<?= BASE_URL ?>/category/<?= h($sub->slug) ?>">
                    <?= h($sub->name) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</nav>

<main>
  <?php if ($current_user && !$current_user->isVerified()): ?>
    <div class="alert alert-warning" style="margin: 0; text-align: center; border-radius: 0; border: none; border-bottom: 1px solid var(--warning-ink); background: var(--warning-bg); color: var(--warning-ink); padding: 0.75rem;">
      <strong>Verify your email:</strong> Please check your inbox to verify your account before you can make a purchase.
      <a href="/verify-email/resend" style="text-decoration: underline; margin-left: 0.5rem;">Resend email</a>
    </div>
  <?php endif; ?>

  <?php
  $msg = $_GET['msg'] ?? '';
  if ($msg === 'verify_sent'): ?>
    <div class="alert alert-info" style="margin: 1rem auto; max-width: 1200px; width: 95%;">
      Verification email sent! Please check your inbox.
    </div>
  <?php elseif ($msg === 'verified'): ?>
    <div class="alert alert-success" style="margin: 1rem auto; max-width: 1200px; width: 95%;">
      Email successfully verified! You can now make purchases.
    </div>
  <?php elseif ($msg === 'verify_invalid'): ?>
    <div class="alert alert-danger" style="margin: 1rem auto; max-width: 1200px; width: 95%;">
      Invalid or expired verification token.
    </div>
  <?php elseif ($msg === 'verify_required'): ?>
    <div class="alert alert-warning" style="margin: 1rem auto; max-width: 1200px; width: 95%;">
      Please verify your email address before checking out.
    </div>
  <?php endif; ?>
