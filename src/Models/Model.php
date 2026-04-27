<?php

namespace App\Models;

use Psr\Log\LoggerInterface;

abstract class Model {
    /**
     * Internal storage for properties that aren't explicitly defined in the class.
     * This avoids the need for #[AllowDynamicProperties] and prevents PHP 8.2+ deprecation notices.
     */
    protected array $unmappedData = [];

    /**
     * Temporary storage for logs generated before the logger is initialized.
     */
    private array $pendingLogs = [];

    /**
     * Proper Dependency Injection via constructor.
     * All models require a logger to handle warnings consistently.
     */
    public function __construct(
        protected LoggerInterface $logger
    ) {
        // Flush any logs that were stashed before the constructor was called
        foreach ($this->pendingLogs as $log) {
            $this->logger->log($log['level'], $log['message']);
        }
        $this->pendingLogs = [];
    }

    /**
     * Safety valve for unexpected database columns during hydration.
     * Instead of creating a dynamic property, we store it in $unmappedData.
     */
    public function __set(string $name, mixed $value): void {
        $this->unmappedData[$name] = $value;
        $className = static::class;
        $message = "Missing property '{$name}' in model '{$className}'. Data stored in unmappedData array.";

        // In PHP 8.1+, typed properties must be checked for initialization to avoid errors.
        // During PDO hydration, __set can be called BEFORE the constructor.
        $loggerInitialized = false;
        try {
            $rp = new \ReflectionProperty(Model::class, 'logger');
            $loggerInitialized = $rp->isInitialized($this);
        } catch (\ReflectionException) {
            // Should not happen as 'logger' is defined
        }

        if ($loggerInitialized) {
            $this->logger->warning($message);
        } else {
            // Stash the log to be flushed in the constructor
            $this->pendingLogs[] = [
                'level' => \Psr\Log\LogLevel::WARNING,
                'message' => $message
            ];
        }
    }

    /**
     * Magic getter to allow access to unmapped properties as if they were real.
     */
    public function __get(string $name): mixed {
        return $this->unmappedData[$name] ?? null;
    }

    /**
     * Magic isset to check unmapped properties.
     */
    public function __isset(string $name): bool {
        return isset($this->unmappedData[$name]);
    }

    /**
     * Fill the model from an associative array.
     */
    public function fill(array $data): self {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            } else {
                // This will trigger __set()
                $this->$key = $value;
            }
        }
        return $this;
    }
}
