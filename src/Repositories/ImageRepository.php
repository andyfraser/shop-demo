<?php
namespace App\Repositories;

use PDO;

class ImageRepository implements ImageRepositoryInterface {
    public function __construct(private PDO $db) {}

    public function getActiveImageNames(): array {
        $stmt = $this->db->query("
            SELECT image FROM products WHERE image IS NOT NULL AND image != ''
            UNION
            SELECT icon FROM categories WHERE icon IS NOT NULL AND icon != ''
        ");
        return array_map('strtolower', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
