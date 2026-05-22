<?php

namespace App\Commands;

use App\Core\Cache\CacheInterface;

class CacheClearCommand implements CommandInterface {
    public function __construct(
        private CacheInterface $cache
    ) {}

    public function getName(): string {
        return 'cache:clear';
    }

    public function getDescription(): string {
        return 'Clears the application cache.';
    }

    public function getSchedule(): ?string {
        return null;
    }

    public function execute(): int {
        if ($this->cache->clear()) {
            echo "Cache cleared successfully.\n";
            return 0;
        }

        echo "Failed to clear cache.\n";
        return 1;
    }
}
