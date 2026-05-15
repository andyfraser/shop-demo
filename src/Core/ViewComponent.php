<?php
namespace App\Core;

interface ViewComponent {
    public function render(): string;
}
