<?php
namespace App\Services;

use App\Core\Database;

class SettingsService {
    private static ?array $cache = null;

    private static array $defaults = [
        'site_name'               => 'Demo|shop',
        'currency_symbol'         => '£',
        'password_min_length'     => '6',
        'login_max_attempts'      => '5',
        'login_window_minutes'    => '15',
        'register_max_attempts'   => '10',
        'register_window_minutes' => '60',
        'low_stock_threshold'     => '10',
    ];

    public static function get(string $key): string {
        if (self::$cache === null) self::load();
        return self::$cache[$key] ?? self::$defaults[$key] ?? '';
    }

    public static function all(): array {
        if (self::$cache === null) self::load();
        return array_merge(self::$defaults, self::$cache);
    }

    public static function set(string $key, string $value): void {
        Database::getConnection()
            ->prepare("REPLACE INTO settings (`key`, value) VALUES (?, ?)")
            ->execute([$key, $value]);
        self::$cache = null;
    }

    private static function load(): void {
        $rows = Database::getConnection()
            ->query("SELECT `key`, value FROM settings")
            ->fetchAll();
        self::$cache = array_column($rows, 'value', 'key');
    }
}
