<?php

require_once __DIR__ . '/../src/Core/Autoloader.php';
\App\Core\Autoloader::register();

require_once __DIR__ . '/../src/Helpers.php';

@session_start();

require_once __DIR__ . '/TestCase.php';

use Tests\AssertionFailedException;

$testDir = __DIR__ . '/Unit';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testDir));
$testFiles = new RegexIterator($files, '/Test\.php$/');

$passed = 0;
$failed = 0;
$assertions = 0;
$failures = [];

echo "Running tests...\n\n";

foreach ($testFiles as $file) {
    require_once $file->getPathname();
    
    // Get classes defined in this file
    $classes = get_declared_classes();
    $testClass = end($classes);
    
    $reflection = new ReflectionClass($testClass);
    if (!$reflection->isAbstract() && $reflection->isSubclassOf('Tests\TestCase')) {
        $instance = $reflection->newInstance();
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if (strpos($method->name, 'test') === 0) {
                try {
                    if ($reflection->hasMethod('setUp')) {
                        $instance->setUp();
                    }
                    $instance->{$method->name}();
                    echo ".";
                    $passed++;
                } catch (AssertionFailedException $e) {
                    echo "F";
                    $failed++;
                    $failures[] = [
                        'class' => $testClass,
                        'method' => $method->name,
                        'message' => $e->getMessage(),
                        'file' => $file->getPathname(),
                        'line' => $e->getLine()
                    ];
                } catch (Exception $e) {
                    echo "E";
                    $failed++;
                    $failures[] = [
                        'class' => $testClass,
                        'method' => $method->name,
                        'message' => "Unhandled Exception: " . $e->getMessage(),
                        'file' => $file->getPathname(),
                        'line' => $e->getLine()
                    ];
                }
            }
        }
        $assertions += $instance->getAssertionCount();
    }
}

echo "\n\n";

if ($failed > 0) {
    echo "Failures:\n";
    foreach ($failures as $i => $failure) {
        echo ($i + 1) . ") {$failure['class']}::{$failure['method']}\n";
        echo "{$failure['message']}\n";
        echo "{$failure['file']}:{$failure['line']}\n\n";
    }
}

echo "Tests: " . ($passed + $failed) . ", Assertions: $assertions, Failures: $failed\n";

exit($failed > 0 ? 1 : 0);
