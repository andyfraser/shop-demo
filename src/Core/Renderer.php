<?php
namespace App\Core;

class Renderer {
    public function __construct(private ViewComposer $viewComposer) {}

    /**
     * Internal method to handle template execution and layout wrapping.
     */
    private function execute(string $templateDir, string $layoutDir, string $template, array $globals, array $vars = []): string {
        // Merge globals and local vars. Local vars take precedence.
        $data = array_merge($globals, $vars);
        extract($data);
        
        ob_start();
        $templateFile = $templateDir . '/' . $template . '.php';
        if (file_exists($templateFile)) {
            require $templateFile;
        } else {
            return "Template not found: " . htmlspecialchars($template, ENT_QUOTES, 'UTF-8');
        }
        $content = ob_get_clean();
        
        ob_start();
        require $layoutDir . '/header.php';
        echo $content;
        require $layoutDir . '/footer.php';
        $output = ob_get_clean();

        // Append Debug Toolbar if in debug mode
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $toolbar = new \App\Core\View\DebugToolbarComponent();
            $output .= $toolbar->render();
        }

        return $output;
    }

    /**
     * Render a storefront template wrapped in storefront header/footer.
     */
    public function render(string $template, array $vars = []): string {
        $globals = $this->viewComposer->getStorefrontGlobals();
        $baseDir = __DIR__ . '/../../templates';
        return $this->execute($baseDir, $baseDir, $template, $globals, $vars);
    }

    /**
     * Render a template without any layout wrapping.
     */
    public function renderPartial(string $template, array $vars = []): string {
        $globals = $this->viewComposer->getStorefrontGlobals();
        $data = array_merge($globals, $vars);
        extract($data);
        
        ob_start();
        $templateFile = __DIR__ . '/../../templates/' . $template . '.php';
        if (file_exists($templateFile)) {
            require $templateFile;
        } else {
            echo "Template not found: " . htmlspecialchars($template, ENT_QUOTES, 'UTF-8');
        }
        return ob_get_clean();
    }

    /**
     * Render an admin template wrapped in admin header/footer.
     */
    public function adminRender(string $template, array $vars = []): string {
        $globals = $this->viewComposer->getAdminGlobals();
        $adminDir = __DIR__ . '/../../templates/admin';
        return $this->execute($adminDir, $adminDir, $template, $globals, $vars);
    }
}
