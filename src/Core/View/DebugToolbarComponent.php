<?php

namespace App\Core\View;

use App\Core\ViewComponent;
use App\Core\DebugCollector;

class DebugToolbarComponent implements ViewComponent {
    public function render(): string {
        $collector = DebugCollector::getInstance();
        $time = number_format($collector->getExecutionTime() * 1000, 2);
        $memory = number_format($collector->getMemoryPeak(), 2);
        $queries = $collector->getQueryCount();
        $hits = $collector->getCacheHits();
        $misses = $collector->getCacheMisses();

        return "
        <div id='debug-toolbar' style='position: fixed; bottom: 0; left: 0; right: 0; height: 30px; background: #222; color: #eee; font-family: monospace; font-size: 12px; display: flex; align-items: center; padding: 0 15px; z-index: 9999; border-top: 1px solid #444;'>
            <div style='margin-right: 20px;'><strong>Debug:</strong></div>
            <div style='margin-right: 20px;'>Time: {$time}ms</div>
            <div style='margin-right: 20px;'>Memory: {$memory}MB</div>
            <div style='margin-right: 20px;'>Queries: {$queries}</div>
            <div style='margin-right: 20px;'>Cache: <span style='color: #2ecc71;'>{$hits} hit</span> / <span style='color: #e74c3c;'>{$misses} miss</span></div>
            <div style='margin-left: auto;'>PHP " . PHP_VERSION . "</div>
        </div>
        ";
    }
}
