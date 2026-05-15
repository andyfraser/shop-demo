<?php
namespace App\Repositories;

class SettingsRepository implements SettingsRepositoryInterface {
    public function __construct(private \PDO $db) {}

    public function getAll(): array {
        try {
            $rows = $this->db->query("SELECT `key`, value FROM settings")
                ->fetchAll();
            return array_column($rows, 'value', 'key');
        } catch (\PDOException $e) {
            if ($e->getCode() === 'HY000' || $e->getCode() === '42S02') {
                return [];
            }
            throw $e;
        }
    }

    public function set(string $key, mixed $value): void {
        $this->db->prepare("REPLACE INTO settings (`key`, value) VALUES (?, ?)")
            ->execute([$key, (string)$value]);
    }
}
