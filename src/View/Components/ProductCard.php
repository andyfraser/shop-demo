<?php
namespace App\View\Components;

use App\Core\BaseComponent;
use App\Models\Product;

class ProductCard extends BaseComponent {
    public function __construct(
        private Product $product,
        private bool $showCategory = false,
        private string $style = '',
        private string $imgWrapStyle = '',
        private string $imgStyle = ''
    ) {}

    protected function getTemplate(): string {
        return 'product_card';
    }

    protected function getContext(): array {
        return [
            'product' => $this->product,
            'showCategory' => $this->showCategory,
            'style' => $this->style,
            'imgWrapStyle' => $this->imgWrapStyle,
            'imgStyle' => $this->imgStyle,
        ];
    }
}
