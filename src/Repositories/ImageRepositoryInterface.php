<?php
namespace App\Repositories;

interface ImageRepositoryInterface {
    public function getActiveImageNames(): array;
}
