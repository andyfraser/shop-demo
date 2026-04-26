<?php
use App\Services\SecurityService;
use App\Services\AuthService;
use App\Services\SettingsService;
use App\Core\Container;

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function money(float $v): string {
    $s = settings();
    return $s->currency_symbol . number_format($v, 2);
}

function setting(string $key): mixed {
    $settings = Container::getInstance()->get(SettingsService::class);
    return $settings->get($key);
}

function settings(): \App\Models\Settings {
    $settings = Container::getInstance()->get(SettingsService::class);
    return $settings->getSettings();
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
    $security = Container::getInstance()->get(SecurityService::class);
    return $security->csrfField();
}

function csrf_token(): string {
    $security = Container::getInstance()->get(SecurityService::class);
    return $security->csrfToken();
}

function is_ajax(): bool {
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

function is_new_product(string $created_at): bool {
    $ts = strtotime($created_at);
    return (time() - $ts) < (7 * 24 * 60 * 60); // 7 days
}

function get_breadcrumb(int $category_id): array {
    $crumbs = [];
    $db = Container::getInstance()->get(\PDO::class);
    $all = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_CLASS, \App\Models\Category::class);
    $map = [];
    foreach ($all as $c) $map[$c->id] = $c;
    
    $current = $map[$category_id] ?? null;
    while ($current) {
        array_unshift($crumbs, $current);
        $current = $current->parent_id ? ($map[$current->parent_id] ?? null) : null;
    }
    return $crumbs;
}
