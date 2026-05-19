<?php
namespace App\Services;

use App\Models\Settings;
use App\Repositories\SettingsRepositoryInterface;
use Psr\Log\LoggerInterface;
use App\Core\Cache\CacheInterface;

class SettingsService implements SettingsServiceInterface {
    private ?Settings $settings = null;

    public function __construct(
        private SettingsRepositoryInterface $repository,
        private LoggerInterface $logger,
        private CacheInterface $cache,
        private \App\Core\Events\EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * Get the typed Settings model.
     */
    public function getSettings(): Settings {
        if ($this->settings === null) {
            $cached = $this->cache->get('app_settings');
            if ($cached !== null) {
                $this->settings = new Settings($this->logger);
                $this->settings->fill($cached);
                return $this->settings;
            }

            $all = $this->repository->getAll();
            $this->settings = new Settings($this->logger);
            $this->settings->fill($all);
            
            $this->cache->set('app_settings', $all, 86400); // Cache for 24h
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
        $oldValue = $this->get($key);
        $this->repository->set($key, $value);
        
        $this->eventDispatcher->dispatch(new \App\Events\SettingUpdated($key, $oldValue, $value));
        
        $this->settings = null; 
        $this->cache->delete('app_settings');
    }
}
