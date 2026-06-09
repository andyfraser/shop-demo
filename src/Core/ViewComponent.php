<?php
namespace App\Core;

interface ViewComponent extends \Stringable {
    public function render(): string;
}
