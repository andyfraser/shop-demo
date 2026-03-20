<?php
// admin/render.php — admin template renderer

function admin_render(string $template, array $vars = []): void {
    $vars['current_user'] = current_user();

    admin_partial('header', $vars);
    admin_partial($template, $vars);
    admin_partial('footer', $vars);
}

function admin_partial(string $template, array $vars = []): void {
    extract($vars, EXTR_SKIP);
    $path = __DIR__ . '/../templates/admin/' . $template . '.php';
    if (!file_exists($path)) {
        throw new RuntimeException("Admin template not found: $template");
    }
    require $path;
}
