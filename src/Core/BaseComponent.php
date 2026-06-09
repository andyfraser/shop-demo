<?php
namespace App\Core;

abstract class BaseComponent implements ViewComponent, \Stringable {
    abstract protected function getTemplate(): string;
    abstract protected function getContext(): array;

    public function render(): string {
        $templatePath = __DIR__ . '/../../templates/components/' . $this->getTemplate() . '.php';
        
        if (!file_exists($templatePath)) {
            return "Component template not found: " . htmlspecialchars($this->getTemplate());
        }

        ob_start();
        // Merge global helpers or custom function results if needed, but context is usually enough
        $data = $this->getContext();
        extract($data);
        require $templatePath;
        return ob_get_clean();
    }

    public function __toString(): string {
        return $this->render();
    }
}
