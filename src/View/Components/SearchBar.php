<?php
namespace App\View\Components;

use App\Core\BaseComponent;

class SearchBar extends BaseComponent {
    public function __construct(
        private string $query = '',
        private bool $isMobile = false
    ) {}

    protected function getTemplate(): string {
        return 'search_bar';
    }

    protected function getContext(): array {
        return [
            'query' => $this->query,
            'isMobile' => $this->isMobile,
        ];
    }
}
