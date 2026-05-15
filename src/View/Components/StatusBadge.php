<?php
namespace App\View\Components;

use App\Core\ViewComponent;

class StatusBadge implements ViewComponent {
    public function __construct(
        private string $label,
        private string $class = 'badge-neutral',
        private string $style = ''
    ) {}

    public function render(): string {
        $label = h($this->label);
        $class = h($this->class);
        $style = $this->style ? ' style="' . h($this->style) . '"' : '';
        
        return "<span class=\"badge {$class}\"{$style}>{$label}</span>";
    }
}
