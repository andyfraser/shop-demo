<?php

namespace App\Core;

use ReflectionClass;
use ReflectionParameter;
use Exception;

class Container {
    private array $services = [];
    private array $instances = [];
    private static ?Container $instance = null;

    public function __construct() {
        self::$instance = $this;
    }

    public static function getInstance(): Container {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function set(string $name, callable $resolver): void {
        $this->services[$name] = $resolver;
    }

    /**
     * Get a service instance.
     */
    public function get(string $name) {
        if ($name === self::class || $name === 'App\Core\Container') {
            return $this;
        }

        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        if (isset($this->services[$name])) {
            $this->instances[$name] = $this->services[$name]($this);
            return $this->instances[$name];
        }

        return $this->resolve($name);
    }

    /**
     * Automatically resolve a class and its dependencies using Reflection.
     */
    private function resolve(string $className) {
        if (!class_exists($className) && !interface_exists($className)) {
            throw new Exception("Class or Interface {$className} does not exist");
        }

        $reflectionClass = new ReflectionClass($className);

        if (!$reflectionClass->isInstantiable()) {
            throw new Exception("Class {$className} is not instantiable");
        }

        $constructor = $reflectionClass->getConstructor();

        if (null === $constructor) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = $this->resolveDependencies($parameters);

        $instance = $reflectionClass->newInstanceArgs($dependencies);
        $this->instances[$className] = $instance;
        
        return $instance;
    }

    /**
     * Resolve constructor parameters.
     */
    private function resolveDependencies(array $parameters): array {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new Exception("Cannot resolve parameter {$parameter->getName()}");
            }

            $dependencies[] = $this->get($type->getName());
        }

        return $dependencies;
    }
}
