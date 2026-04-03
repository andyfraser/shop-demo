<?php // templates/admin/header.php
// Expects: $page_title, $active, $current_user
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($page_title) ?> — Admin — <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/public/css/admin.css">
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">
    Demoshop
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
    <a href="/admin/orders"     class="<?= $active === 'orders'     ? 'active' : '' ?>">
      <span class="ico">🧾</span> Orders
    </a>
    <div class="sidebar-section">Users</div>
    <a href="/admin/users"      class="<?= $active === 'users'      ? 'active' : '' ?>">
      <span class="ico">👥</span> Users
    </a>
  </nav>

  <div class="sidebar-foot">
    <a href="/">← View Store</a>
    <a href="/logout">Sign out</a>
  </div>
</aside>

<div class="admin-main">
