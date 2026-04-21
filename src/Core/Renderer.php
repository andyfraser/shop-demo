<?php
namespace App\Core;

class Renderer {
    /**
     * Internal method to handle template execution and layout wrapping.
     */
    private static function execute(string $templateDir, string $layoutDir, string $template, array $globals, array $vars = []) {
        // Merge globals and local vars. Local vars take precedence.
        $data = array_merge($globals, $vars);
        extract($data);
        
        ob_start();
        $templateFile = $templateDir . '/' . $template . '.php';
        if (file_exists($templateFile)) {
            require $templateFile;
        } else {
            header("HTTP/1.0 404 Not Found");
            echo "Template not found: " . htmlspecialchars($template, ENT_QUOTES, 'UTF-8');
        }
        $content = ob_get_clean();
        
        require $layoutDir . '/header.php';
        echo $content;
        require $layoutDir . '/footer.php';
    }

    /**
     * Render a storefront template wrapped in storefront header/footer.
     */
    public static function render(string $template, array $vars = []) {
        $globals = ViewComposer::getStorefrontGlobals();
        $baseDir = __DIR__ . '/../../templates';
        self::execute($baseDir, $baseDir, $template, $globals, $vars);
    }

    /**
     * Render an admin template wrapped in admin header/footer.
     */
    public static function adminRender(string $template, array $vars = []) {
        $globals = ViewComposer::getAdminGlobals();
        $adminDir = __DIR__ . '/../../templates/admin';
        self::execute($adminDir, $adminDir, $template, $globals, $vars);
    }
}
