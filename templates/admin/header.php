<?php // templates/admin/header.php
// Expects: $page_title, $active, $current_user
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($page_title) ?> — Admin — <?= SITE_NAME_PLAIN ?></title>
<link rel="icon" type="image/svg+xml" href="/public/images/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/public/css/admin.css?v=<?= filemtime(__DIR__ . '/../../public/css/admin.css') ?>">
</head>
<body>

<div class="admin-wrap">
<aside class="sidebar">
  <div class="sidebar-logo">
    <?= h(SITE_NAME_PLAIN) ?>
    <small>Admin Panel</small>
  </div>

  <div class="sidebar-section">Store</div>
  <nav>
    <a href="/admin" class="<?= $active === 'dashboard' ? 'active' : '' ?>">
      <span class="ico">📊</span> Dashboard
    </a>
    <a href="/admin/products"   class="<?= $active === 'products'   ? 'active' : '' ?>">
      <span class="ico">📦</span> Products
    </a>
    <a href="/admin/categories" class="<?= $active === 'categories' ? 'active' : '' ?>">
      <span class="ico">🏷</span> Categories
    </a>
    <a href="/admin/attributes" class="<?= $active === 'attributes' ? 'active' : '' ?>">
      <span class="ico">🔧</span> Attributes
    </a>
    <a href="/admin/orders"     class="<?= $active === 'orders'     ? 'active' : '' ?>">
      <span class="ico">🧾</span> Orders
    </a>
    <a href="/admin/returns"    class="<?= $active === 'returns'    ? 'active' : '' ?>">
      <span class="ico">🔄</span> Returns
    </a>
    <a href="/admin/reviews"    class="<?= $active === 'reviews'    ? 'active' : '' ?>">
      <span class="ico">💬</span> Reviews
    </a>
    <a href="/admin/delivery"   class="<?= $active === 'delivery'   ? 'active' : '' ?>">
      <span class="ico">🚚</span> Delivery
    </a>
    <a href="/admin/promotions" class="<?= $active === 'promotions' ? 'active' : '' ?>">
      <span class="ico">🎟</span> Promotions
    </a>
    <div class="sidebar-section">Users</div>
    <a href="/admin/users"      class="<?= $active === 'users'      ? 'active' : '' ?>">
      <span class="ico">👥</span> Users
    </a>
    <a href="/admin/user-roles" class="<?= $active === 'user-roles' ? 'active' : '' ?>">
      <span class="ico">🛡</span> User Roles
    </a>
    <div class="sidebar-section">Config</div>
    <a href="/admin/settings"   class="<?= $active === 'settings'   ? 'active' : '' ?>">
      <span class="ico">⚙️</span> Settings
    </a>
    <a href="/admin/backup"     class="<?= $active === 'backup'     ? 'active' : '' ?>">
      <span class="ico">💾</span> Database
    </a>
  </nav>

  <div class="sidebar-foot">
    <a href="/">← View Store</a>
    <a href="/logout">Sign out</a>
  </div>
</aside>

<div class="admin-main">
  <div class="flash-container">
    <?php foreach (['success', 'msg', 'error', 'msg_error', 'info', 'warning'] as $key): ?>
      <?php if ($f = flash($key)): ?>
        <?php 
          $type = (strpos($key, 'error') !== false || $key === 'err') ? 'error' : 
                  ((strpos($key, 'success') !== false || $key === 'msg') ? 'success' : $key);
        ?>
        <div class="alert alert-<?= $type ?>" onclick="this.remove()"><?= h($f) ?></div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const alerts = document.querySelectorAll('.flash-container .alert');
      alerts.forEach(alert => {
        setTimeout(() => {
          alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
          alert.style.opacity = '0';
          alert.style.transform = 'translateX(20px)';
          setTimeout(() => alert.remove(), 500);
        }, 10000);
      });
    });
  </script>
