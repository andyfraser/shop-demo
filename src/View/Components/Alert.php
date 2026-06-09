<?php
namespace App\View\Components;

use App\Core\BaseComponent;

class Alert extends BaseComponent {
    public function __construct(
        private string $message,
        private string $type = 'success',
        private bool $dismissible = true
    ) {}

    protected function getTemplate(): string {
        return 'alert';
    }

    protected function getContext(): array {
        return [
            'message' => $this->message,
            'type' => $this->type,
            'dismissible' => $this->dismissible,
        ];
    }
}
