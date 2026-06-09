<?php
namespace App\View\Components;

use App\Core\BaseComponent;

class Breadcrumbs extends BaseComponent {
    private array $items = [];

    /**
     * @param array $crumbs Array of items. Each item can be:
     *                      - [ 'label' => '...', 'url' => '...' ]
     *                      - or key/value pair: 'Label' => 'URL' (e.g. ['Home' => '/'])
     *                      - or string (label with no url)
     */
    public function __construct(array $crumbs) {
        foreach ($crumbs as $key => $val) {
            if (is_array($val)) {
                $this->items[] = [
                    'label' => $val['label'] ?? '',
                    'url' => $val['url'] ?? null
                ];
            } elseif (is_string($key)) {
                $this->items[] = [
                    'label' => $key,
                    'url' => $val
                ];
            } else {
                $this->items[] = [
                    'label' => $val,
                    'url' => null
                ];
            }
        }
    }

    protected function getTemplate(): string {
        return 'breadcrumbs';
    }

    protected function getContext(): array {
        return [
            'items' => $this->items,
        ];
    }
}
