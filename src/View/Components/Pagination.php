<?php
namespace App\View\Components;

use App\Core\BaseComponent;

class Pagination extends BaseComponent {
    public function __construct(
        private int $currentPage,
        private int $totalPages,
        private ?string $baseUrl = null,
        private array $queryParams = []
    ) {
        if ($this->baseUrl === null) {
            $uri = $_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI'] ?? '/';
            $this->baseUrl = explode('?', $uri)[0];
        }
        if (empty($this->queryParams)) {
            $this->queryParams = $_GET;
        }
        unset($this->queryParams['ajax']);
    }

    protected function getTemplate(): string {
        return 'pagination';
    }

    protected function getContext(): array {
        return [
            'currentPage' => $this->currentPage,
            'totalPages' => $this->totalPages,
            'baseUrl' => $this->baseUrl,
            'queryParams' => $this->queryParams,
        ];
    }
}
