<?php
namespace App\View\Components;

use App\Core\ViewComponent;

class Alert implements ViewComponent {
    public function __construct(
        private string $message,
        private string $type = 'success',
        private bool $dismissible = true
    ) {}

    public function render(): string {
        $message = h($this->message);
        $type = h($this->type);
        $onclick = $this->dismissible ? ' onclick="this.remove()"' : '';
        
        return "<div class=\"alert alert-{$type}\"{$onclick}>{$message}</div>";
    }
}
