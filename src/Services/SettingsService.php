<?php
namespace App\Services;

use App\Core\Database;

class SettingsService {
    private ?array $cache = null;

    private array $defaults = [
        'site_name'               => 'Demo|shop',
        'currency_symbol'         => '£',
        'password_min_length'     => '6',
        'login_max_attempts'      => '5',
        'login_window_minutes'    => '15',
        'register_max_attempts'   => '10',
        'register_window_minutes' => '60',
        'low_stock_threshold'     => '10',
        'remember_me_days'        => '30',
        'default_vat_rate'        => '20',
        'mobile_nav_max_top'      => '10',
        'mobile_nav_max_combined' => '20',
    ];

    public function __construct(private \PDO $db) {}

    public function get(string $key): string {
        if ($this->cache === null) $this->load();
        return $this->cache[$key] ?? $this->defaults[$key] ?? '';
    }

    public function all(): array {
        if ($this->cache === null) $this->load();
        return array_merge($this->defaults, $this->cache);
    }

    public function set(string $key, string $value): void {
        $this->db->prepare("REPLACE INTO settings (`key`, value) VALUES (?, ?)")
            ->execute([$key, $value]);
        $this->cache = null;
    }

    private function load(): void {
        $rows = $this->db->query("SELECT `key`, value FROM settings")
            ->fetchAll();
        $this->cache = array_column($rows, 'value', 'key');
    }
}
