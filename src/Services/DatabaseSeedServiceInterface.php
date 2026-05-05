<?php

namespace App\Services;

interface DatabaseSeedServiceInterface {
    /**
     * Seed the database with initial data.
     * 
     * @return void
     */
    public function seed(): void;
}
