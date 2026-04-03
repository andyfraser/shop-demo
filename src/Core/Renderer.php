<?php
namespace App\Core;

use App\Services\CartService;
use App\Services\AuthService;

class Renderer {
    public static function render(string $template, array $vars = []) {
        extract($vars);
        $cart_count = CartService::count();
        $current_user = AuthService::currentUser();
        $nav_tree = get_category_tree();
        
        ob_start();
        $templateFile = __DIR__ . '/../../templates/' . $template . '.php';
        if (file_exists($templateFile)) {
            require $templateFile;
        } else {
            echo "Template not found: {$template}";
        }
        $content = ob_get_clean();
        
        require __DIR__ . '/../../templates/header.php';
        echo $content;
        require __DIR__ . '/../../templates/footer.php';
    }

    public static function adminRender(string $template, array $vars = []) {
        extract($vars);
        $current_user = AuthService::currentUser();
        
        ob_start();
        $templateFile = __DIR__ . '/../../templates/admin/' . $template . '.php';
        if (file_exists($templateFile)) {
            require $templateFile;
        } else {
            echo "Template not found: {$template}";
        }
        $content = ob_get_clean();
        
        require __DIR__ . '/../../templates/admin/header.php';
        echo $content;
        require __DIR__ . '/../../templates/admin/footer.php';
    }
}
