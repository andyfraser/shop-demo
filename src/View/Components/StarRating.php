<?php
namespace App\View\Components;

use App\Core\BaseComponent;

class StarRating extends BaseComponent {
    public function __construct(
        private float $rating,
        private bool $showNumeric = false,
        private string $style = ''
    ) {}

    protected function getTemplate(): string {
        return 'star_rating';
    }

    protected function getContext(): array {
        $fullStars = floor($this->rating);
        $halfStar = ($this->rating - $fullStars) >= 0.5 ? 1 : 0;
        $emptyStars = 5 - $fullStars - $halfStar;

        return [
            'rating' => $this->rating,
            'fullStars' => (int)$fullStars,
            'halfStar' => (bool)$halfStar,
            'emptyStars' => (int)$emptyStars,
            'showNumeric' => $this->showNumeric,
            'style' => $this->style,
        ];
    }
}
