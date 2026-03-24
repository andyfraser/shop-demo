<?php
// ─── Configuration ────────────────────────────────────────────────────────────
define('DB_PATH', __DIR__ . '/shop.db');
define('SITE_NAME', 'Demoshop');

define('BASE_URL', '');

// ─── Database ─────────────────────────────────────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        init_db($pdo);
    }
    return $pdo;
}

function init_db(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    // Migration: rename image_url -> image and clear old remote URLs
    $pcols = $pdo->query("PRAGMA table_info(products)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (in_array('image_url', $pcols) && !in_array('image', $pcols)) {
        $pdo->exec("ALTER TABLE products RENAME COLUMN image_url TO image");
        // Clear Unsplash URLs — they'll show placeholder until images are uploaded
        $pdo->exec("UPDATE products SET image = NULL WHERE image LIKE '%unsplash%'");
    }

    // Migration: add icon column before running schema so seed INSERT can use it
    $cols = $pdo->query("PRAGMA table_info(categories)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if ($cols && !in_array('icon', $cols)) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN icon TEXT");
        $icons = [
            'electronics'  => '💻', 'clothing'     => '👕', 'home-garden'  => '🏠',
            'laptops'      => '💻', 'phones'       => '📱', 'audio'        => '🎧',
            'mens'         => '👔', 'womens'       => '👗', 'kitchen'      => '🍳',
            'garden-tools' => '🌱',
        ];
        $stmt = $pdo->prepare("UPDATE categories SET icon = ? WHERE slug = ?");
        foreach ($icons as $slug => $icon) {
            $stmt->execute([$icon, $slug]);
        }
    }

    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schema);

    // Seed demo users with properly hashed passwords
    $hash = password_hash('password', PASSWORD_DEFAULT);
    $pdo->prepare(
        "INSERT OR IGNORE INTO users (id, name, email, password_hash, role)
         VALUES (1, 'Admin', 'admin@shop.local', ?, 'admin')"
    )->execute([$hash]);
    $pdo->prepare(
        "INSERT OR IGNORE INTO users (id, name, email, password_hash, role)
         VALUES (2, 'Jane Smith', 'jane@example.com', ?, 'customer')"
    )->execute([$hash]);
}

// ─── Session ──────────────────────────────────────────────────────────────────
function session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function current_user(): ?array {
    session();
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool {
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

function require_login(): void {
    if (!current_user()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function require_admin(): void {
    if (!current_user()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    if (!is_admin()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

// ─── Cart ─────────────────────────────────────────────────────────────────────
function cart(): array {
    session();
    return $_SESSION['cart'] ?? [];
}

function cart_add(int $product_id, int $qty = 1): void {
    session();
    $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + $qty;
}

function cart_remove(int $product_id): void {
    session();
    unset($_SESSION['cart'][$product_id]);
}

function cart_update(int $product_id, int $qty): void {
    session();
    if ($qty <= 0) { cart_remove($product_id); return; }
    $_SESSION['cart'][$product_id] = $qty;
}

function cart_clear(): void {
    session();
    $_SESSION['cart'] = [];
}

function cart_count(): int {
    return array_sum(cart());
}

function cart_items(): array {
    $c = cart();
    if (empty($c)) return [];
    $ids = implode(',', array_map('intval', array_keys($c)));
    $rows = db()->query("SELECT * FROM products WHERE id IN ($ids)")->fetchAll();
    foreach ($rows as &$row) {
        $row['qty'] = $c[$row['id']];
        $row['subtotal'] = $row['price'] * $row['qty'];
    }
    return $rows;
}

function cart_total(): float {
    return array_sum(array_column(cart_items(), 'subtotal'));
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Placeholder data URI shown when no image URL is set or the URL fails to load
// Render a product image tag — shows placeholder if no image or load fails
function product_img(string $filename = '', string $alt = '', string $class = '', string $style = ''): void {
    $placeholder = BASE_URL . '/public/images/placeholder.svg';
    $src   = $filename ? h(BASE_URL . '/public/images/' . $filename) : $placeholder;
    $alt   = h($alt);
    $class = $class ? ' class="' . h($class) . '"' : '';
    $style = $style ? ' style="' . h($style) . '"' : '';
    echo "<img src=\"{$src}\" alt=\"{$alt}\"{$class}{$style} onerror=\"this.onerror=null;this.src='{$placeholder}'\">";
}

function slugify(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function money(float $v): string {
    return '£' . number_format($v, 2);
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function flash(string $key, ?string $msg = null): ?string {
    session();
    if ($msg !== null) {
        $_SESSION['flash'][$key] = $msg;
        return null;
    }
    $val = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $val;
}

// ─── Category helpers ─────────────────────────────────────────────────────────
function get_category_tree(): array {
    $all = db()->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    $tree = [];
    $map = [];
    foreach ($all as $c) $map[$c['id']] = $c + ['children' => []];
    foreach ($map as &$c) {
        if ($c['parent_id']) $map[$c['parent_id']]['children'][] = &$c;
        else $tree[] = &$c;
    }
    return $tree;
}

function get_category_flat(): array {
    return db()->query(
        "SELECT c.*, p.name as parent_name
         FROM categories c
         LEFT JOIN categories p ON c.parent_id = p.id
         ORDER BY COALESCE(p.name, c.name), c.parent_id IS NOT NULL, c.name"
    )->fetchAll();
}

function get_breadcrumb(int $category_id): array {
    $crumbs = [];
    $all = db()->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    $all = array_column($all, null, 'id');
    $current = $all[$category_id] ?? null;
    while ($current) {
        array_unshift($crumbs, $current);
        $current = $current['parent_id'] ? ($all[$current['parent_id']] ?? null) : null;
    }
    return $crumbs;
}
