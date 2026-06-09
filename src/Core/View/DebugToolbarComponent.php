<?php

namespace App\Core\View;

use App\Core\ViewComponent;
use App\Core\DebugCollector;

class DebugToolbarComponent implements ViewComponent {
    public function render(): string {
        $collector = DebugCollector::getInstance();
        $time = number_format($collector->getExecutionTime() * 1000, 1);
        $memory = number_format($collector->getMemoryPeak(), 2);
        $queriesCount = $collector->getQueryCount();
        $queries = $collector->getQueries();
        $hits = $collector->getCacheHits();
        $misses = $collector->getCacheMisses();
        $cacheOps = $collector->getCacheOperations();
        
        // Fetch logs for active request
        $logs = $collector->getLogs();
        
        // Fetch and clear redirect logs from session
        $redirectLogs = [];
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['__debug_redirect_logs'])) {
            $redirectLogs = $_SESSION['__debug_redirect_logs'];
            unset($_SESSION['__debug_redirect_logs']);
        }

        // Merge redirect logs into logs
        $allLogs = [];
        foreach ($redirectLogs as $log) {
            $log['is_redirect'] = true;
            $allLogs[] = $log;
        }
        foreach ($logs as $log) {
            $log['is_redirect'] = false;
            $allLogs[] = $log;
        }

        $milestones = $collector->getMilestones();
        $slowThreshold = $collector->getSlowQueryThreshold();
        $matchedRoute = $collector->getMatchedRoute();

        // Calculate slow queries count
        $slowQueriesCount = 0;
        $totalQueriesDuration = 0;
        foreach ($queries as $q) {
            $durationMs = $q['duration'] * 1000;
            $totalQueriesDuration += $durationMs;
            if ($durationMs >= $slowThreshold) {
                $slowQueriesCount++;
            }
        }
        $totalQueriesDuration = number_format($totalQueriesDuration, 2);

        // Count warning/error logs
        $warningLogsCount = 0;
        $errorLogsCount = 0;
        foreach ($allLogs as $log) {
            $lvl = strtolower($log['level']);
            if ($lvl === 'warning') {
                $warningLogsCount++;
            } elseif (in_array($lvl, ['error', 'critical', 'alert', 'emergency'])) {
                $errorLogsCount++;
            }
        }

        // Cache hit rate calculation
        $totalCacheOps = $hits + $misses;
        $hitRate = $totalCacheOps > 0 ? number_format(($hits / $totalCacheOps) * 10000 / 100, 1) : 0;

        // Render sections
        $requestTabHtml = $this->renderRequestTab($matchedRoute);
        $queriesTabHtml = $this->renderQueriesTab($queries, $slowThreshold, $totalQueriesDuration);
        $cacheTabHtml = $this->renderCacheTab($cacheOps, $hits, $misses, $hitRate);
        $logsTabHtml = $this->renderLogsTab($allLogs, $warningLogsCount, $errorLogsCount);
        $perfTabHtml = $this->renderPerformanceTab($milestones, $collector->getExecutionTime(), $memory);

        // UI styling & template
        return "
        <!-- Antigravity Premium Debug Toolbar & Drawer -->
        <style>
            #debug-root {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 999999;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji';
                color: #e2e8f0;
                font-size: 13px;
                box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.45);
                transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .debug-blur-glass {
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                background: rgba(18, 20, 26, 0.88);
                border-top: 1px solid rgba(255, 255, 255, 0.08);
            }
            #debug-bar {
                height: 38px;
                display: flex;
                align-items: center;
                padding: 0 16px;
                cursor: default;
                user-select: none;
            }
            .debug-bar-item {
                display: flex;
                align-items: center;
                padding: 0 12px;
                height: 100%;
                border-right: 1px solid rgba(255, 255, 255, 0.05);
                cursor: pointer;
                transition: background 0.15s ease, color 0.15s ease;
                color: #cbd5e1;
            }
            .debug-bar-item:hover {
                background: rgba(255, 255, 255, 0.05);
                color: #ffffff;
            }
            .debug-bar-item.active {
                background: rgba(255, 255, 255, 0.08);
                color: #ffffff;
                box-shadow: inset 0 -2px 0 #38bdf8;
            }
            .debug-bar-brand {
                font-weight: 800;
                letter-spacing: 0.5px;
                color: #38bdf8;
                padding-right: 16px;
                border-right: 1px solid rgba(255, 255, 255, 0.08);
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .debug-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 1.5px 6px;
                font-size: 11px;
                font-weight: 700;
                border-radius: 4px;
                margin-left: 6px;
                line-height: 1;
            }
            .debug-badge-info { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.2); }
            .debug-badge-warning { background: rgba(245, 158, 11, 0.2); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); box-shadow: 0 0 10px rgba(245, 158, 11, 0.1); }
            .debug-badge-danger { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); box-shadow: 0 0 10px rgba(239, 68, 68, 0.15); }
            .debug-badge-success { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.2); }
            
            #debug-drawer {
                height: 380px;
                display: none;
                border-top: 1px solid rgba(255, 255, 255, 0.05);
                overflow: hidden;
            }
            .debug-drawer-content {
                display: none;
                height: 100%;
                overflow-y: auto;
                padding: 20px;
                box-sizing: border-box;
            }
            .debug-drawer-content.active {
                display: block;
            }
            .debug-drawer-content::-webkit-scrollbar {
                width: 8px;
            }
            .debug-drawer-content::-webkit-scrollbar-track {
                background: rgba(0, 0, 0, 0.2);
            }
            .debug-drawer-content::-webkit-scrollbar-thumb {
                background: rgba(255, 255, 255, 0.1);
                border-radius: 4px;
            }
            .debug-drawer-content::-webkit-scrollbar-thumb:hover {
                background: rgba(255, 255, 255, 0.2);
            }

            /* Utilities & Layouts */
            .debug-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 20px;
            }
            .debug-card {
                background: rgba(30, 41, 59, 0.4);
                border: 1px solid rgba(255, 255, 255, 0.05);
                border-radius: 8px;
                padding: 16px;
                box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            }
            .debug-card-title {
                font-weight: 700;
                color: #ffffff;
                margin-top: 0;
                margin-bottom: 12px;
                font-size: 14px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                padding-bottom: 8px;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .debug-table {
                width: 100%;
                border-collapse: collapse;
                text-align: left;
                font-size: 12.5px;
            }
            .debug-table th {
                font-weight: 700;
                color: #94a3b8;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                padding: 8px 12px;
            }
            .debug-table td {
                padding: 8px 12px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.03);
                color: #cbd5e1;
                vertical-align: top;
            }
            .debug-mono {
                font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
            }
            
            /* Query card specific */
            .query-card {
                background: rgba(15, 23, 42, 0.6);
                border: 1px solid rgba(255, 255, 255, 0.06);
                border-radius: 6px;
                margin-bottom: 12px;
                overflow: hidden;
            }
            .query-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: rgba(30, 41, 59, 0.5);
                padding: 8px 16px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            }
            .query-body {
                padding: 12px 16px;
            }
            .query-sql {
                white-space: pre-wrap;
                word-break: break-all;
                margin: 0;
                line-height: 1.5;
                font-size: 13px;
                color: #e2e8f0;
            }
            .query-params {
                margin-top: 10px;
                padding-top: 10px;
                border-top: 1px dotted rgba(255, 255, 255, 0.08);
                font-size: 11.5px;
            }
            .btn-copy {
                background: rgba(255, 255, 255, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.1);
                color: #cbd5e1;
                padding: 3px 8px;
                border-radius: 4px;
                font-size: 11px;
                cursor: pointer;
                transition: background 0.15s, color 0.15s;
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }
            .btn-copy:hover {
                background: rgba(255, 255, 255, 0.15);
                color: #ffffff;
            }
            
            /* Performance Gantt timeline */
            .perf-timeline {
                display: flex;
                height: 24px;
                background: rgba(255, 255, 255, 0.05);
                border-radius: 6px;
                overflow: hidden;
                margin-bottom: 24px;
                border: 1px solid rgba(255, 255, 255, 0.08);
            }
            .perf-segment {
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11px;
                font-weight: 700;
                color: #0f172a;
                text-shadow: 0 1px 2px rgba(255,255,255,0.2);
                transition: opacity 0.15s ease;
                cursor: help;
                min-width: 25px;
            }
            .perf-segment:hover {
                opacity: 0.95;
            }
        </style>

        <div id='debug-root' class='debug-blur-glass'>
            <!-- Mini bar summary -->
            <div id='debug-bar'>
                <div class='debug-bar-brand' onclick='toggleDebugDrawer()'>
                    <svg width=\"14\" height=\"14\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><polygon points=\"5 3 19 12 5 21 5 3\"></polygon></svg>
                    <span>DEMOSHOP</span>
                </div>
                
                <div class='debug-bar-item' data-tab='tab-request'>
                    📂 Request: <span class='debug-mono' style='margin-left:5px; color:#38bdf8; font-weight:bold;'>" . htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'GET') . "</span> <span style='margin-left:5px; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color:#94a3b8;'>" . htmlspecialchars(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)) . "</span>
                </div>
                
                <div class='debug-bar-item' data-tab='tab-queries'>
                    💾 Queries: <strong style='margin-left:4px; color:#f8fafc;'>{$queriesCount}</strong> 
                    <span style='color:#94a3b8; margin-left:4px;'>({$totalQueriesDuration}ms)</span>
                    " . ($slowQueriesCount > 0 ? "<span class='debug-badge debug-badge-warning'>{$slowQueriesCount} slow</span>" : "") . "
                </div>
                
                <div class='debug-bar-item' data-tab='tab-cache'>
                    ⚡ Cache: <strong style='margin-left:4px; color:#f8fafc;'>{$hitRate}%</strong>
                    <span style='color:#94a3b8; margin-left:4px;'>({$hits}h / {$misses}m)</span>
                </div>
                
                <div class='debug-bar-item' data-tab='tab-logs'>
                    📋 Logs: <strong style='margin-left:4px; color:#f8fafc;'>" . count($allLogs) . "</strong>
                    " . ($errorLogsCount > 0 ? "<span class='debug-badge debug-badge-danger'>{$errorLogsCount} err</span>" : "") . "
                    " . ($warningLogsCount > 0 ? "<span class='debug-badge debug-badge-warning'>{$warningLogsCount} wrn</span>" : "") . "
                </div>
                
                <div class='debug-bar-item' data-tab='tab-perf'>
                    📐 Time: <strong style='margin-left:4px; color:#4ade80;'>{$time}ms</strong>
                    <span style='color:#94a3b8; margin: 0 10px;'>|</span>
                    Memory: <strong style='color:#38bdf8;'>{$memory}MB</strong>
                </div>
                
                <div style='margin-left: auto; display: flex; align-items: center; gap: 12px; color: #64748b;'>
                    <span class='debug-mono' style='font-size:11px;'>PHP " . PHP_VERSION . "</span>
                    <button onclick='toggleDebugDrawer()' style='background:none; border:none; color:#cbd5e1; cursor:pointer; padding:4px; display:flex; align-items:center; transition:color 0.15s;' id='debug-collapse-btn'>
                        <svg width=\"16\" height=\"16\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><polyline points=\"18 15 12 9 6 15\"></polyline></svg>
                    </button>
                </div>
            </div>
            
            <!-- Drawer expanded details -->
            <div id='debug-drawer'>
                <div id='tab-request' class='debug-drawer-content'>
                    {$requestTabHtml}
                </div>
                <div id='tab-queries' class='debug-drawer-content'>
                    {$queriesTabHtml}
                </div>
                <div id='tab-cache' class='debug-drawer-content'>
                    {$cacheTabHtml}
                </div>
                <div id='tab-logs' class='debug-drawer-content'>
                    {$logsTabHtml}
                </div>
                <div id='tab-perf' class='debug-drawer-content'>
                    {$perfTabHtml}
                </div>
            </div>
        </div>

        <script>
            (function() {
                var drawer = document.getElementById('debug-drawer');
                var items = document.querySelectorAll('.debug-bar-item');
                var contents = document.querySelectorAll('.debug-drawer-content');
                var collapseBtn = document.getElementById('debug-collapse-btn');
                var activeTab = localStorage.getItem('debug_active_tab') || null;
                var isOpen = localStorage.getItem('debug_is_open') === 'true';

                // Initial setup
                if (isOpen && activeTab) {
                    var targetTab = document.getElementById(activeTab);
                    var barItem = document.querySelector('[data-tab=\"' + activeTab + '\"]');
                    if (targetTab && barItem) {
                        drawer.style.display = 'block';
                        targetTab.classList.add('active');
                        barItem.classList.add('active');
                        updateCollapseBtn(true);
                    }
                }

                items.forEach(function(item) {
                    item.addEventListener('click', function() {
                        var tabId = this.getAttribute('data-tab');
                        
                        if (isOpen && this.classList.contains('active')) {
                            // Close drawer if clicking currently active tab
                            closeDrawer();
                        } else {
                            // Open drawer / Switch tab
                            openTab(tabId, this);
                        }
                    });
                });

                window.toggleDebugDrawer = function() {
                    if (isOpen) {
                        closeDrawer();
                    } else {
                        // Open first tab or last active
                        var firstTab = activeTab || 'tab-request';
                        var firstItem = document.querySelector('[data-tab=\"' + firstTab + '\"]');
                        if (firstItem) {
                            openTab(firstTab, firstItem);
                        }
                    }
                };

                function openTab(tabId, barItem) {
                    isOpen = true;
                    activeTab = tabId;
                    localStorage.setItem('debug_is_open', 'true');
                    localStorage.setItem('debug_active_tab', tabId);

                    drawer.style.display = 'block';
                    
                    contents.forEach(c => c.classList.remove('active'));
                    items.forEach(i => i.classList.remove('active'));

                    document.getElementById(tabId).classList.add('active');
                    barItem.classList.add('active');
                    updateCollapseBtn(true);
                }

                function closeDrawer() {
                    isOpen = false;
                    localStorage.setItem('debug_is_open', 'false');
                    drawer.style.display = 'none';
                    items.forEach(i => i.classList.remove('active'));
                    updateCollapseBtn(false);
                }

                function updateCollapseBtn(expanded) {
                    if (expanded) {
                        collapseBtn.innerHTML = '<svg width=\"16\" height=\"16\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><polyline points=\"6 9 12 15 18 9\"></polyline></svg>';
                    } else {
                        collapseBtn.innerHTML = '<svg width=\"16\" height=\"16\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><polyline points=\"18 15 12 9 6 15\"></polyline></svg>';
                    }
                }

                window.copyToClipboard = function(text, btn) {
                    var onSuccess = function() {
                        var originalHtml = btn.innerHTML;
                        btn.innerHTML = '<span style=\"color:#4ade80;\">✓ Copied!</span>';
                        setTimeout(function() {
                            btn.innerHTML = originalHtml;
                        }, 2000);
                    };
                    var onError = function(err) {
                        alert('Could not copy text: ' + err);
                    };

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(onSuccess, onError);
                    } else {
                        try {
                            var textArea = document.createElement(\"textarea\");
                            textArea.value = text;
                            textArea.style.top = \"0\";
                            textArea.style.left = \"0\";
                            textArea.style.position = \"fixed\";
                            textArea.style.opacity = \"0\";
                            document.body.appendChild(textArea);
                            textArea.focus();
                            textArea.select();
                            var successful = document.execCommand(\"copy\");
                            document.body.removeChild(textArea);
                            if (successful) {
                                onSuccess();
                            } else {
                                onError(\"copy command failed\");
                            }
                        } catch (err) {
                            onError(err);
                        }
                    }
                };
            })();
        </script>
        ";
    }

    private function renderRequestTab(?array $route): string {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        if (empty($headers)) {
            foreach ($_SERVER as $name => $value) {
                if (str_starts_with($name, 'HTTP_')) {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }

        $sessionData = $_SESSION ?? [];
        
        $routeHtml = "";
        if ($route) {
            $controller = htmlspecialchars($route['handler'][0] ?? 'unknown');
            $action = htmlspecialchars($route['handler'][1] ?? 'unknown');
            $middlewares = implode(', ', array_map('htmlspecialchars', $route['middlewares'] ?? []));
            $routeHtml = "
            <div class='debug-card' style='margin-bottom: 20px;'>
                <div class='debug-card-title'>Matched Route: <span style='color:#38bdf8; font-weight:800; font-family:monospace;'>{$route['path']}</span></div>
                <table class='debug-table'>
                    <tr><td style='width:140px; font-weight:bold;'>Controller Class</td><td class='debug-mono'>{$controller}</td></tr>
                    <tr><td style='font-weight:bold;'>Action Method</td><td class='debug-mono' style='color:#f472b6;'>{$action}()</td></tr>
                    <tr><td style='font-weight:bold;'>Middlewares</td><td class='debug-mono' style='color:#fbbf24;'>" . ($middlewares ?: 'none') . "</td></tr>
                </table>
            </div>";
        } else {
            $routeHtml = "
            <div class='debug-card' style='margin-bottom: 20px;'>
                <div class='debug-card-title'>Matched Route</div>
                <p style='color:#94a3b8; font-style:italic; margin:0;'>No controller route matched (likely serving static file or 404).</p>
            </div>";
        }

        // Format Session Values
        $sessionRows = "";
        if (!empty($sessionData)) {
            foreach ($sessionData as $key => $value) {
                $formattedValue = is_scalar($value) ? htmlspecialchars((string)$value) : '<span style="color:#f472b6;">' . htmlspecialchars(json_encode($value)) . '</span>';
                $sessionRows .= "<tr><td class='debug-mono' style='font-weight:bold; color:#a78bfa;'>{$key}</td><td class='debug-mono'>{$formattedValue}</td></tr>";
            }
        } else {
            $sessionRows = "<tr><td colspan='2' style='color:#64748b; font-style:italic; text-align:center;'>Session is empty</td></tr>";
        }

        // Format Headers
        $headerRows = "";
        foreach ($headers as $key => $value) {
            $headerRows .= "<tr><td style='font-weight:bold; width:220px; color:#f472b6;'>{$key}</td><td class='debug-mono'>{$value}</td></tr>";
        }

        return "
        <h3 style='margin-top:0; margin-bottom:16px; font-size:16px; color:#ffffff;'>📂 Request & Session Profiler</h3>
        <div class='debug-grid'>
            <div style='display:flex; flex-direction:column; gap:20px; grid-column: span 1;'>
                {$routeHtml}
                <div class='debug-card'>
                    <div class='debug-card-title'>Active Session Variables</div>
                    <table class='debug-table'>
                        {$sessionRows}
                    </table>
                </div>
            </div>
            <div class='debug-card' style='grid-column: span 1;'>
                <div class='debug-card-title'>HTTP Request Headers</div>
                <div style='max-height: 250px; overflow-y:auto;'>
                    <table class='debug-table'>
                        {$headerRows}
                    </table>
                </div>
            </div>
        </div>";
    }

    private function renderQueriesTab(array $queries, float $slowThreshold, string $totalDuration): string {
        $queriesHtml = "";
        if (empty($queries)) {
            return "
            <h3 style='margin-top:0; margin-bottom:16px; font-size:16px; color:#ffffff;'>💾 SQL Database Queries</h3>
            <p style='color:#94a3b8; font-style:italic;'>No SQL queries were executed during this request.</p>";
        }

        foreach ($queries as $index => $q) {
            $sql = $q['sql'];
            $params = $q['params'];
            $durationMs = $q['duration'] * 1000;
            $isSlow = $durationMs >= $slowThreshold;

            $badgeClass = $isSlow ? 'debug-badge-danger' : 'debug-badge-info';
            $durationText = number_format($durationMs, 2) . ' ms';
            
            // Build absolute query with bindings in place for convenience
            $executableSql = $this->bindSqlParams($sql, $params);
            $highlightedSql = $this->highlightSql($executableSql);

            $paramsHtml = "";
            if (!empty($params)) {
                $paramsList = [];
                $isZeroIndexed = array_key_exists(0, $params);
                foreach ($params as $key => $val) {
                    if (is_numeric($key)) {
                        $paramNum = $isZeroIndexed ? ((int)$key + 1) : (int)$key;
                        $paramName = '?' . $paramNum;
                    } else {
                        $paramName = htmlspecialchars((string)$key);
                    }
                    $paramVal = is_null($val) ? '<span style="color:#f87171;">NULL</span>' : htmlspecialchars(json_encode($val));
                    $paramsList[] = "<span style='color:#a78bfa; font-weight:bold;'>{$paramName}</span> => {$paramVal}";
                }
                $paramsHtml = "<div class='query-params'><strong style='color:#94a3b8;'>Bindings:</strong> " . implode(', ', $paramsList) . "</div>";
            }

            $queriesHtml .= "
            <div class='query-card'>
                <div class='query-header'>
                    <div>
                        <strong style='color:#64748b;'>#" . ($index + 1) . "</strong>
                        <span class='debug-badge {$badgeClass}' style='margin-left: 10px;'>{$durationText}</span>
                        " . ($isSlow ? "<span class='debug-badge debug-badge-warning' style='margin-left: 6px;'>Slow (>= {$slowThreshold}ms)</span>" : "") . "
                    </div>
                    <button class='btn-copy' data-sql=\"" . htmlspecialchars($executableSql, ENT_QUOTES, 'UTF-8') . "\" onclick=\"copyToClipboard(this.getAttribute('data-sql'), this)\">
                        <svg width=\"12\" height=\"12\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><rect x=\"9\" y=\"9\" width=\"13\" height=\"13\" rx=\"2\" ry=\"2\"></rect><path d=\"M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1\"></path></svg>
                        Copy Clean SQL
                    </button>
                </div>
                <div class='query-body'>
                    <pre class='query-sql debug-mono'>{$highlightedSql}</pre>
                    {$paramsHtml}
                </div>
            </div>";
        }

        return "
        <div style='display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;'>
            <h3 style='margin:0; font-size:16px; color:#ffffff;'>💾 SQL Database Queries</h3>
            <span style='color:#94a3b8;'>Executed <strong style='color:#ffffff;'>" . count($queries) . "</strong> queries in <strong style='color:#4ade80;'>{$totalDuration}ms</strong> (slow query threshold is <span style='color:#f59e0b;'>{$slowThreshold}ms</span>)</span>
        </div>
        <div style='max-height: 300px; overflow-y: auto;'>
            {$queriesHtml}
        </div>";
    }

    private function renderCacheTab(array $ops, int $hits, int $misses, string $hitRate): string {
        $rows = "";
        if (empty($ops)) {
            $rows = "<tr><td colspan='4' style='color:#64748b; font-style:italic; text-align:center;'>No cache interactions on this request</td></tr>";
        } else {
            foreach ($ops as $idx => $op) {
                $statusBadge = $op['status'] === 'hit' ? "<span class='debug-badge debug-badge-success'>HIT</span>" : ($op['status'] === 'miss' ? "<span class='debug-badge debug-badge-danger'>MISS</span>" : "<span class='debug-badge debug-badge-info'>OK</span>");
                $timeFormatted = number_format($op['time'] * 1000, 2) . 'ms';
                $rows .= "
                <tr>
                    <td style='font-weight:bold; color:#64748b;'>#" . ($idx + 1) . "</td>
                    <td class='debug-mono' style='color:#38bdf8; text-transform:uppercase;'>{$op['operation']}</td>
                    <td class='debug-mono' style='word-break:break-all;'>{$op['key']}</td>
                    <td>{$statusBadge}</td>
                    <td class='debug-mono' style='text-align:right;'>{$timeFormatted}</td>
                </tr>";
            }
        }

        return "
        <h3 style='margin-top:0; margin-bottom:16px; font-size:16px; color:#ffffff;'>⚡ Cache Operations Tracker</h3>
        <div class='debug-grid'>
            <div class='debug-card' style='grid-column: span 1; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center;'>
                <div style='font-size: 13px; color: #94a3b8; font-weight:700; margin-bottom: 5px;'>CACHE HIT RATE</div>
                <div style='font-size: 48px; font-weight: 800; color: #4ade80; line-height: 1;'>{$hitRate}%</div>
                <div style='margin-top:15px; display:flex; gap:16px; font-size:12.5px;'>
                    <div>Hits: <strong style='color:#4ade80;'>{$hits}</strong></div>
                    <div style='color:#475569;'>|</div>
                    <div>Misses: <strong style='color:#f87171;'>{$misses}</strong></div>
                </div>
            </div>
            
            <div class='debug-card' style='grid-column: span 2;'>
                <div class='debug-card-title'>Granular Cache Activity Log</div>
                <div style='max-height: 230px; overflow-y:auto;'>
                    <table class='debug-table'>
                        <thead>
                            <tr>
                                <th style='width:30px;'>#</th>
                                <th style='width:80px;'>Op</th>
                                <th>Cache Key</th>
                                <th style='width:70px;'>Result</th>
                                <th style='width:85px; text-align:right;'>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$rows}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>";
    }

    private function renderLogsTab(array $logs, int $warningCount, int $errorCount): string {
        $rows = "";
        if (empty($logs)) {
            $rows = "<div style='color:#94a3b8; font-style:italic; padding: 12px; background: rgba(30, 41, 59, 0.2); border-radius: 6px; text-align: center; border: 1px dashed rgba(255,255,255,0.05);'>No application logs were recorded for this request context.</div>";
        } else {
            foreach ($logs as $idx => $log) {
                $level = strtoupper($log['level']);
                $timeFormatted = number_format($log['time'] * 1000, 1) . 'ms';
                
                $badgeColorClass = 'debug-badge-info';
                $leftBorderColor = '#38bdf8';
                if ($level === 'WARNING') {
                    $badgeColorClass = 'debug-badge-warning';
                    $leftBorderColor = '#f59e0b';
                } elseif (in_array($level, ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'])) {
                    $badgeColorClass = 'debug-badge-danger';
                    $leftBorderColor = '#ef4444';
                }

                $isRedirect = $log['is_redirect'] ?? false;
                $redirectBadge = $isRedirect ? "<span class='debug-badge debug-badge-info' style='margin-left: 6px; background: rgba(167, 139, 250, 0.15); color: #a78bfa; border: 1px solid rgba(167, 139, 250, 0.2);'>REDIRECT</span>" : "";

                $contextHtml = "";
                if (!empty($log['context'])) {
                    $contextHtml = "<div style='margin-top:4px; font-size:11px; color:#64748b;' class='debug-mono'>" . htmlspecialchars(json_encode($log['context'])) . "</div>";
                }

                $rows .= "
                <div style='display:flex; align-items:flex-start; padding: 10px 14px; border-left: 3px solid {$leftBorderColor}; background: rgba(30, 41, 59, 0.3); border-bottom: 1px solid rgba(255, 255, 255, 0.03); margin-bottom:6px; border-radius: 0 4px 4px 0;'>
                    <div style='min-width: 90px;'>
                        <span class='debug-badge {$badgeColorClass}'>{$level}</span>
                        {$redirectBadge}
                    </div>
                    <div style='flex-grow:1; word-break:break-all; font-size:12.5px;'>
                        <div style='color:#e2e8f0;'>{$log['message']}</div>
                        {$contextHtml}
                    </div>
                    <div class='debug-mono' style='color:#64748b; font-size:11px; margin-left: 15px;'>
                        {$timeFormatted}
                    </div>
                </div>";
            }
        }

        // Load config file to get physical log file locations
        $config = [];
        $configFile = __DIR__ . '/../../../config/config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
        } else {
            $configFileExample = __DIR__ . '/../../../config/config.example.php';
            if (file_exists($configFileExample)) {
                $config = require $configFileExample;
            }
        }

        $appLogFile = $config['app']['log_path'] ?? __DIR__ . '/../../../logs/app.log';
        $errorLogFile = $config['app']['error_log_path'] ?? __DIR__ . '/../../../logs/error.log';

        $recentAppLogs = $this->getRecentLogLines($appLogFile, 30);
        $recentErrorLogs = $this->getRecentLogLines($errorLogFile, 30);

        $formatLogLine = function(string $line) {
            $line = htmlspecialchars($line);
            if (strpos($line, 'ERROR') !== false || strpos($line, 'CRITICAL') !== false) {
                return "<div style='color:#f87171; margin-bottom:4px; border-left: 2px solid #ef4444; padding-left: 6px;'>{$line}</div>";
            } elseif (strpos($line, 'WARNING') !== false) {
                return "<div style='color:#fbbf24; margin-bottom:4px; border-left: 2px solid #f59e0b; padding-left: 6px;'>{$line}</div>";
            } elseif (strpos($line, 'INFO') !== false) {
                return "<div style='color:#38bdf8; margin-bottom:4px; border-left: 2px solid #38bdf8; padding-left: 6px;'>{$line}</div>";
            }
            return "<div style='color:#cbd5e1; margin-bottom:4px;'>{$line}</div>";
        };

        $appLogsHtml = "";
        if (!empty($recentAppLogs)) {
            foreach ($recentAppLogs as $line) {
                $appLogsHtml .= $formatLogLine($line);
            }
        } else {
            $appLogsHtml = "<div style='color:#64748b; font-style:italic;'>No logs found in app.log</div>";
        }

        $errorLogsHtml = "";
        if (!empty($recentErrorLogs)) {
            foreach ($recentErrorLogs as $line) {
                $errorLogsHtml .= $formatLogLine($line);
            }
        } else {
            $errorLogsHtml = "<div style='color:#64748b; font-style:italic;'>No logs found in error.log</div>";
        }

        return "
        <div style='display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;'>
            <h3 style='margin:0; font-size:16px; color:#ffffff;'>📋 Application System Logs</h3>
            <span style='color:#94a3b8;'>Recorded <strong style='color:#ffffff;'>" . count($logs) . "</strong> logs on this request (" . ($errorCount > 0 ? "<span style='color:#f87171; font-weight:bold;'>{$errorCount} Errors</span>" : "0 Errors") . ", " . ($warningCount > 0 ? "<span style='color:#f59e0b; font-weight:bold;'>{$warningCount} Warnings</span>" : "0 Warnings") . ")</span>
        </div>
        <div style='max-height: 200px; overflow-y:auto; margin-bottom: 20px;'>
            {$rows}
        </div>
        
        <div class='debug-grid' style='margin-top: 16px; border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 16px;'>
            <div class='debug-card' style='grid-column: span 1;'>
                <div class='debug-card-title'>Recent App Log (logs/app.log)</div>
                <div style='max-height: 180px; overflow-y:auto; font-family: SFMono-Regular, Consolas, monospace; font-size: 11px; background: rgba(15, 23, 42, 0.4); padding: 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.05); text-align: left;'>
                    {$appLogsHtml}
                </div>
            </div>
            <div class='debug-card' style='grid-column: span 1;'>
                <div class='debug-card-title'>Recent Error Log (logs/error.log)</div>
                <div style='max-height: 180px; overflow-y:auto; font-family: SFMono-Regular, Consolas, monospace; font-size: 11px; background: rgba(15, 23, 42, 0.4); padding: 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.05); text-align: left;'>
                    {$errorLogsHtml}
                </div>
            </div>
        </div>";
    }

    private function renderPerformanceTab(array $milestones, float $totalTime, string $memory): string {
        $totalTimeMs = $totalTime * 1000;
        
        // Gantt-style timeline items calculations
        $segmentsHtml = "";
        $timelineBreakdownRows = "";
        
        $colors = [
            'Boot' => '#38bdf8', // Sky Blue
            'Bootstrap & DI' => '#a78bfa', // Purple
            'Routing & Middleware' => '#fbbf24', // Amber
            'Controller & Rendering' => '#34d399' // Green
        ];

        // Format timeline items
        $lastTime = 0.0;
        foreach ($milestones as $idx => $m) {
            $name = $m['name'];
            $milestoneTimeMs = $m['time'] * 1000;
            
            // Duration of this segment is from the last milestone to this one
            $segmentDuration = max(0.0, $milestoneTimeMs - $lastTime);
            $percentage = $totalTimeMs > 0 ? ($segmentDuration / $totalTimeMs) * 100 : 0;
            
            $color = $colors[$name] ?? '#94a3b8';
            
            if ($percentage > 0.5) { // Only render segment if visible width
                $durationFormatted = number_format($segmentDuration, 1) . 'ms';
                $segmentsHtml .= "<div class='perf-segment' style='width: {$percentage}%; background: {$color};' title='{$name}: {$durationFormatted} ({$percentage}%)'></div>";
            }
            
            $timelineBreakdownRows .= "
            <tr>
                <td><span style='display:inline-block; width:12px; height:12px; border-radius:3px; background:{$color}; margin-right:8px; vertical-align:middle;'></span><strong>{$name}</strong></td>
                <td class='debug-mono' style='text-align:right; color:#ffffff;'>" . number_format($milestoneTimeMs, 2) . " ms</td>
                <td class='debug-mono' style='text-align:right; color:#4ade80;'>" . number_format($segmentDuration, 2) . " ms</td>
                <td class='debug-mono' style='text-align:right; color:#94a3b8;'>" . number_format($percentage, 1) . "%</td>
            </tr>";
            
            $lastTime = $milestoneTimeMs;
        }

        // Remaining time after final milestone
        $remainingTime = max(0.0, $totalTimeMs - $lastTime);
        if ($remainingTime > 0.1) {
            $percentage = $totalTimeMs > 0 ? ($remainingTime / $totalTimeMs) * 100 : 0;
            if ($percentage > 0.5) {
                $segmentsHtml .= "<div class='perf-segment' style='width: {$percentage}%; background: #475569;' title='Final Cleanups: " . number_format($remainingTime, 1) . "ms'></div>";
            }
            $timelineBreakdownRows .= "
            <tr>
                <td><span style='display:inline-block; width:12px; height:12px; border-radius:3px; background:#475569; margin-right:8px; vertical-align:middle;'></span><strong>Output & Shutdown</strong></td>
                <td class='debug-mono' style='text-align:right; color:#ffffff;'>" . number_format($totalTimeMs, 2) . " ms</td>
                <td class='debug-mono' style='text-align:right; color:#4ade80;'>" . number_format($remainingTime, 2) . " ms</td>
                <td class='debug-mono' style='text-align:right; color:#94a3b8;'>" . number_format($percentage, 1) . "%</td>
            </tr>";
        }

        return "
        <h3 style='margin-top:0; margin-bottom:16px; font-size:16px; color:#ffffff;'>📐 Lifecycle Execution Profiler</h3>
        
        <div class='perf-timeline'>
            {$segmentsHtml}
        </div>

        <div class='debug-grid'>
            <div class='debug-card' style='grid-column: span 1; display:flex; flex-direction:column; justify-content:space-around;'>
                <div>
                    <div style='font-size: 12px; color: #94a3b8; font-weight:700; margin-bottom: 2px;'>TOTAL EXECUTION TIME</div>
                    <div style='font-size: 28px; font-weight: 800; color: #4ade80;'>" . number_format($totalTimeMs, 1) . " ms</div>
                </div>
                <div style='margin-top:15px; border-top: 1px solid rgba(255,255,255,0.06); padding-top:15px;'>
                    <div style='font-size: 12px; color: #94a3b8; font-weight:700; margin-bottom: 2px;'>PEAK MEMORY ALLOCATED</div>
                    <div style='font-size: 28px; font-weight: 800; color: #38bdf8;'>{$memory} MB</div>
                </div>
            </div>
            
            <div class='debug-card' style='grid-column: span 2;'>
                <div class='debug-card-title'>Application Milestones Timeline Breakdown</div>
                <div style='max-height: 210px; overflow-y:auto;'>
                    <table class='debug-table'>
                        <thead>
                            <tr>
                                <th>Milestone Phase</th>
                                <th style='text-align:right; width:95px;'>Elapsed Time</th>
                                <th style='text-align:right; width:95px;'>Phase Duration</th>
                                <th style='text-align:right; width:75px;'>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$timelineBreakdownRows}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>";
    }

    private function highlightSql(string $sql): string {
        $sql = $this->dedentSql($sql);
        $keywords = ['SELECT', 'FROM', 'WHERE', 'AND', 'OR', 'JOIN', 'LEFT', 'RIGHT', 'INNER', 'ON', 'GROUP', 'BY', 'ORDER', 'LIMIT', 'OFFSET', 'UPDATE', 'SET', 'INSERT', 'INTO', 'VALUES', 'DELETE', 'PRAGMA', 'FOREIGN', 'KEYS', 'CREATE', 'TABLE', 'IF', 'NOT', 'EXISTS', 'ASC', 'DESC', 'IN', 'IS', 'NULL', 'LIKE', 'AS'];
        
        // Escape HTML tags to prevent cross site scripting (ignoring quotes for string highlighting)
        $sql = htmlspecialchars($sql, ENT_NOQUOTES, 'UTF-8');

        // Compile regex for single-pass lexing to avoid style collisions
        // Group 1: Single-quoted strings
        // Group 2: SQL Keywords
        // Group 3: Numeric literals
        $regex = '/(\'[^\']*\')|\b(' . implode('|', $keywords) . ')\b|\b(\d+)\b/i';

        return preg_replace_callback($regex, function($matches) {
            if (!empty($matches[1])) {
                // String literal
                return '<span style="color:#ce9178;">' . $matches[1] . '</span>';
            } elseif (!empty($matches[2])) {
                // SQL keyword
                return '<span style="color:#569cd6; font-weight:bold;">' . strtoupper($matches[2]) . '</span>';
            } elseif (isset($matches[3]) && $matches[3] !== '') {
                // Numeric literal
                return '<span style="color:#b5cea8;">' . $matches[3] . '</span>';
            }
            return $matches[0];
        }, $sql);
    }

    private function dedentSql(string $sql): string {
        $lines = explode("\n", $sql);
        if (count($lines) <= 1) {
            return trim($sql);
        }
        
        // Filter out empty lines to avoid miscalculating min indentation
        $nonEmptyLines = array_filter(array_map('rtrim', $lines));
        if (empty($nonEmptyLines)) {
            return trim($sql);
        }
        
        // Find leading spaces of each line
        $indents = [];
        foreach ($nonEmptyLines as $line) {
            // Skip first line if it has no leading space (often the case in inline SQL strings)
            if ($line === reset($nonEmptyLines) && !str_starts_with($line, ' ') && !str_starts_with($line, "\t")) {
                continue;
            }
            
            if (preg_match('/^([ \t]*)/', $line, $match)) {
                $indents[] = strlen($match[1]);
            }
        }
        
        $minIndent = !empty($indents) ? min($indents) : 0;
        
        if ($minIndent > 0) {
            $lines = array_map(function($line) use ($minIndent) {
                if (preg_match('/^([ \t]*)/', $line, $match)) {
                    $len = strlen($match[1]);
                    if ($len >= $minIndent) {
                        return substr($line, $minIndent);
                    }
                }
                return ltrim($line);
            }, $lines);
        }
        
        return trim(implode("\n", $lines));
    }

    private function bindSqlParams(string $sql, array $params): string {
        if (empty($params)) {
            return $sql;
        }

        $isPositional = array_key_exists(0, $params) || isset($params[1]);

        if ($isPositional) {
            // Sort keys numerically to ensure they are replaced in correct positional order
            ksort($params);
            foreach ($params as $val) {
                $quoted = is_numeric($val) ? $val : "'" . addslashes((string)$val) . "'";
                $pos = strpos($sql, '?');
                if ($pos !== false) {
                    $sql = substr_replace($sql, $quoted, $pos, 1);
                }
            }
        } else {
            // Sort keys by descending length to prevent partial replacement bugs
            uksort($params, function($a, $b) {
                return strlen($b) - strlen($a);
            });

            foreach ($params as $key => $val) {
                $quoted = is_numeric($val) ? $val : (is_null($val) ? 'NULL' : "'" . addslashes((string)$val) . "'");
                $paramKey = str_starts_with($key, ':') ? $key : ':' . $key;
                $sql = str_replace($paramKey, $quoted, $sql);
            }
        }

        return $sql;
    }

    /**
     * Fast, memory-efficient tail implementation to read the last N lines of a log file.
     */
    private function getRecentLogLines(string $filePath, int $limit = 30): array {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [];
        }

        $fileSize = filesize($filePath);
        if ($fileSize === 0) {
            return [];
        }

        $fp = fopen($filePath, 'r');
        if (!$fp) {
            return [];
        }

        // Read at most the last 100KB of the file
        $readBytes = min($fileSize, 102400); // 100KB
        fseek($fp, -$readBytes, SEEK_END);
        $chunk = fread($fp, $readBytes);
        fclose($fp);

        if ($chunk === false) {
            return [];
        }

        $lines = explode("\n", $chunk);
        // Remove trailing empty line if it exists
        if (end($lines) === '') {
            array_pop($lines);
        }

        // Return the last N lines
        return array_slice($lines, -$limit);
    }

    public function __toString(): string {
        return $this->render();
    }
}
