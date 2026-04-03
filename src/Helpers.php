<?php
use App\Services\SecurityService;
use App\Services\AuthService;
use App\Services\CartService;

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function money(float $v): string {
    return \App\Services\SettingsService::get('currency_symbol') . number_format($v, 2);
}

function setting(string $key): string {
    return \App\Services\SettingsService::get($key);
}

function product_img(string $filename = '', string $alt = '', string $class = '', string $style = ''): void {
    $placeholder = '/public/images/placeholder.svg';
    $src   = $filename ? h('/public/images/' . $filename) : $placeholder;
    $alt   = h($alt);
    $class = $class ? ' class="' . h($class) . '"' : '';
    $style = $style ? ' style="' . h($style) . '"' : '';
    echo "<img src=\"{$src}\" alt=\"{$alt}\"{$class}{$style} onerror=\"this.onerror=null;this.src='{$placeholder}'\">";
}

function flash(string $key, ?string $msg = null): ?string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if ($msg !== null) {
        $_SESSION['flash'][$key] = $msg;
        return null;
    }
    $val = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $val;
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function slugify(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function csrf_field(): string {
    return SecurityService::csrfField();
}

function csrf_token(): string {
    return SecurityService::csrfToken();
}

function is_ajax(): bool {
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

function current_user(): ?array {
    return AuthService::currentUser();
}

function is_admin(): bool {
    return AuthService::isAdmin();
}

function cart_count(): int {
    return CartService::count();
}

function get_category_tree(): array {
    $all = App\Core\Database::getConnection()->query("SELECT * FROM categories ORDER BY name")->fetchAll();
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
    return App\Core\Database::getConnection()->query(
        "SELECT c.*, p.name as parent_name
         FROM categories c
         LEFT JOIN categories p ON c.parent_id = p.id
         ORDER BY COALESCE(p.name, c.name), c.parent_id IS NOT NULL, c.name"
    )->fetchAll();
}

function get_breadcrumb(int $category_id): array {
    $crumbs = [];
    $all = App\Core\Database::getConnection()->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    $all = array_column($all, null, 'id');
    $current = $all[$category_id] ?? null;
    while ($current) {
        array_unshift($crumbs, $current);
        $current = $current['parent_id'] ? ($all[$current['parent_id']] ?? null) : null;
    }
    return $crumbs;
}
