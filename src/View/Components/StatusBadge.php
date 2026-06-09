<?php
namespace App\View\Components;

use App\Core\BaseComponent;

class StatusBadge extends BaseComponent {
    public function __construct(
        private string $label,
        private string $class = 'badge-neutral',
        private string $style = ''
    ) {}

    protected function getTemplate(): string {
        return 'status_badge';
    }

    protected function getContext(): array {
        return [
            'label' => $this->label,
            'class' => $this->class,
            'style' => $this->style,
        ];
    }
}
