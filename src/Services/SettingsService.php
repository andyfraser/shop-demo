<?php
namespace App\Services;

use App\Models\Settings;
use App\Repositories\SettingsRepositoryInterface;
use Psr\Log\LoggerInterface;

class SettingsService implements SettingsServiceInterface {
    private ?Settings $settings = null;

    public function __construct(
        private SettingsRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}

    /**
     * Get the typed Settings model.
     */
    public function getSettings(): Settings {
        if ($this->settings === null) {
            $this->settings = new Settings($this->logger);
            $this->settings->fill($this->repository->getAll());
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
        $this->repository->set($key, $value);
        $this->logger->info("Setting {key} was updated to value {value}", ['key' => $key, 'value' => $value]);
        $this->settings = null; // Clear cache
    }
}
