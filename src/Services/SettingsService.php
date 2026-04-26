<?php
namespace App\Services;

use App\Models\Settings;

class SettingsService implements SettingsServiceInterface {
    private ?Settings $settings = null;

    public function __construct(private \PDO $db) {}

    /**
     * Get the typed Settings model.
     */
    public function getSettings(): Settings {
        if ($this->settings === null) {
            $this->settings = new Settings();
            $this->settings->fill($this->loadFromDb());
        }
        return $this->settings;
    }

    /**
     * Legacy support for individual key access.
     */
    public function get(string $key): mixed {
        $settings = $this->getSettings();
        return $settings->$key ?? null;
    }

    /**
     * Legacy support for getting all settings as an array.
     */
    public function all(): array {
        return (array)$this->getSettings();
    }

    /**
     * Persist a setting value.
     */
    public function set(string $key, mixed $value): void {
        $this->db->prepare("REPLACE INTO settings (`key`, value) VALUES (?, ?)")
            ->execute([$key, (string)$value]);
        $this->settings = null; // Clear cache
    }

    private function loadFromDb(): array {
        $rows = $this->db->query("SELECT `key`, value FROM settings")
            ->fetchAll();
        return array_column($rows, 'value', 'key');
    }
}
