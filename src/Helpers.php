<?php
use App\Services\SecurityService;
use App\Services\AuthService;
use App\Services\SettingsService;
use App\Core\Container;

/**
 * Format a UTC timestamp into the locally configured timezone for display.
 */
function format_local_time(string $utcTime, string $format = 'Y-m-d H:i:s'): string {
    try {
        $tzName = setting('timezone') ?: 'Europe/London';
        $date = new DateTime($utcTime, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($tzName));
        return $date->format($format);
    } catch (\Exception $e) {
        return $utcTime;
    }
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function money(float $v): string {
    $pricing = Container::getInstance()->get(\App\Services\PricingServiceInterface::class);
    return $pricing->format($v);
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

function product_img(string $filename = '', string $alt = '', string $class = '', string $style = '', string $size = 'original', string $sizes = ''): void {
    $alt   = h($alt);
    $class = $class ? ' class="' . h($class) . '"' : '';
    $style = $style ? ' style="' . h($style) . '"' : '';

    if (!$filename) {
        $src = product_img_url('', $size);
        echo "<img src=\"{$src}\" alt=\"{$alt}\"{$class}{$style} loading=\"lazy\">";
        return;
    }

    $src = product_img_url($filename, $size);
    
    // Generate srcset for responsive delivery
    $srcset = [];
    $placeholder = (defined('BASE_URL') ? BASE_URL : '') . '/images/placeholder.svg';
    
    $thumb  = product_img_url($filename, 'thumb');
    $medium = product_img_url($filename, 'medium');
    $large  = product_img_url($filename, 'large');

    if ($thumb !== $placeholder) $srcset[] = "{$thumb} 400w";
    if ($medium !== $placeholder) $srcset[] = "{$medium} 800w";
    if ($large !== $placeholder) $srcset[] = "{$large} 1200w";

    $srcsetAttr = '';
    $sizesAttr = '';

    if (!empty($srcset)) {
        $srcsetAttr = ' srcset="' . implode(', ', $srcset) . '"';
        if ($sizes) {
            $sizesAttr = ' sizes="' . h($sizes) . '"';
        } else {
            // Default sizes: roughly matches the grid/detail layouts
            $sizesAttr = ' sizes="(max-width: 800px) 100vw, 800px"';
        }
    }

    echo "<img src=\"{$src}\" alt=\"{$alt}\"{$srcsetAttr}{$sizesAttr}{$class}{$style} loading=\"lazy\">";
}

function flash(string $key, ?string $msg = null): ?string {
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            session_start();
        } elseif (!isset($_SESSION)) {
            $_SESSION = [];
        }
    }
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
