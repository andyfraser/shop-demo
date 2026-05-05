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

function product_img_url(string $filename = '', string $size = 'original'): string {
    $imageService = Container::getInstance()->get(\App\Services\ImageServiceInterface::class);
    $url = $imageService->getUrl($filename, $size);
    return (defined('BASE_URL') ? BASE_URL : '') . $url;
}

function product_img(string $filename = '', string $alt = '', string $class = '', string $style = '', string $size = 'original'): void {
    $src   = product_img_url($filename, $size);
    $alt   = h($alt);
    $class = $class ? ' class="' . h($class) . '"' : '';
    $style = $style ? ' style="' . h($style) . '"' : '';
    echo "<img src=\"{$src}\" alt=\"{$alt}\"{$class}{$style}>";
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

/**
 * Get the most prominent active promotion for a product.
 */
function get_active_promotion(mixed $product): ?\App\Models\Promotion {
    $promos = is_object($product) ? ($product->active_promotions ?? []) : ($product['active_promotions'] ?? []);
    if (empty($promos)) return null;
    
    // Sort by value descending to show best offer
    usort($promos, fn($a, $b) => $b->value <=> $a->value);
    return $promos[0];
}

/**
 * Output a promotion badge for product listings.
 * Returns true if a badge was rendered, false otherwise.
 */
function promotion_badge(mixed $product): bool {
    $promo = get_active_promotion($product);
    if (!$promo) return false;

    $label = '';
    if ($promo->type === 'percentage') {
        $label = (int)$promo->value . '% OFF';
    } elseif ($promo->type === 'fixed_amount') {
        $label = 'SALE';
    } elseif ($promo->type === 'free_shipping') {
        $label = 'FREE SHIPPING';
    } elseif ($promo->type === 'buy_x_get_y') {
        $label = 'BUY ' . $promo->buy_qty . ' GET ' . $promo->get_qty;
    }

    if ($label) {
        echo '<span class="product-badge badge-promo">' . h($label) . '</span>';
        return true;
    }
    return false;
}
