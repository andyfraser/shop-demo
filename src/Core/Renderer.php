<?php
namespace App\Core;

class Renderer {
    public function __construct(private ViewComposer $viewComposer) {}

    /**
     * Internal method to handle template execution and layout wrapping.
     */
    private function execute(string $templateDir, string $layoutDir, string $template, array $globals, array $vars = []) {
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
    public function render(string $template, array $vars = []) {
        $globals = $this->viewComposer->getStorefrontGlobals();
        $baseDir = __DIR__ . '/../../templates';
        $this->execute($baseDir, $baseDir, $template, $globals, $vars);
    }

    /**
     * Render a template without any layout wrapping.
     */
    public function renderPartial(string $template, array $vars = []) {
        $globals = $this->viewComposer->getStorefrontGlobals();
        $data = array_merge($globals, $vars);
        extract($data);
        
        $templateFile = __DIR__ . '/../../templates/' . $template . '.php';
        if (file_exists($templateFile)) {
            require $templateFile;
        } else {
            echo "Template not found: " . htmlspecialchars($template, ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Render an admin template wrapped in admin header/footer.
     */
    public function adminRender(string $template, array $vars = []) {
        $globals = $this->viewComposer->getAdminGlobals();
        $adminDir = __DIR__ . '/../../templates/admin';
        $this->execute($adminDir, $adminDir, $template, $globals, $vars);
    }
}
