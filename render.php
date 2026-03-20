<?php
// render.php — template renderer
// Usage: render('template_name', ['var' => 'value'])
// Also: render_partial('template_name', ['var' => 'value'])

function render(string $template, array $vars = []): void {
    // Standard variables always available in every template
    $vars['current_user'] = current_user();
    $vars['cart_count']   = cart_count();
    $vars['nav_tree']     = get_category_tree();

    render_partial('header', $vars);
    render_partial($template, $vars);
    render_partial('footer', $vars);
}

function render_partial(string $template, array $vars = []): void {
    extract($vars, EXTR_SKIP);
    $path = __DIR__ . '/templates/' . $template . '.php';
    if (!file_exists($path)) {
        throw new RuntimeException("Template not found: $template");
    }
    require $path;
}
