<?php
// Handle AJAX installer actions
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    $action = $_GET['action'];
    
    if ($action === 'test_connection') {
        $driver = $_POST['driver'] ?? 'sqlite';
        try {
            if ($driver === 'sqlite') {
                $path = $_POST['sqlite_path'] ?? '';
                if (empty($path)) {
                    throw new Exception("SQLite database file path cannot be empty.");
                }
                
                // If it's a relative path, normalize it relative to index.php
                if ($path[0] !== '/' && $path[1] !== ':') {
                    $path = __DIR__ . '/../' . $path;
                }
                
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    if (!@mkdir($dir, 0755, true)) {
                        throw new Exception("Failed to create SQLite directory: " . $dir);
                    }
                }
                if (!is_writable($dir)) {
                    throw new Exception("SQLite directory is not writable: " . $dir);
                }
                
                $pdo = new PDO('sqlite:' . $path);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } else if ($driver === 'mysql') {
                $host = $_POST['mysql_host'] ?? '127.0.0.1';
                $port = $_POST['mysql_port'] ?? '3306';
                $dbname = $_POST['mysql_dbname'] ?? 'shop_demo';
                $user = $_POST['mysql_user'] ?? 'root';
                $pass = $_POST['mysql_pass'] ?? '';
                $charset = 'utf8mb4';
                
                // Try direct connection first
                try {
                    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
                    $pdo = new PDO($dsn, $user, $pass, [
                        PDO::ATTR_TIMEOUT => 3, // 3 seconds timeout
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                } catch (PDOException $e) {
                    // Database not found, try to connect to server directly to verify credentials
                    if ($e->getCode() == 1049) {
                        $dsn = "mysql:host={$host};port={$port};charset={$charset}";
                        $pdo = new PDO($dsn, $user, $pass, [
                            PDO::ATTR_TIMEOUT => 3,
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                        ]);
                    } else {
                        throw $e;
                    }
                }
            } else {
                throw new Exception("Unsupported driver: " . $driver);
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'install') {
        $logs = [];
        try {
            $driver = $_POST['driver'] ?? 'sqlite';
            $seed = isset($_POST['seed']) && $_POST['seed'] === 'true';
            $adminEmail = $_POST['admin_email'] ?? 'admin@shop.local';
            $adminPassword = $_POST['admin_password'] ?? 'password';
            
            $baseUrlMode = $_POST['base_url_mode'] ?? 'clean';
            $baseUrlVal = ($baseUrlMode === 'restricted') ? '/public' : '';
            
            $logs[] = "Initializing DemoShop Web Onboarding Installer...";
            
            if ($driver === 'sqlite') {
                $path = $_POST['sqlite_path'] ?? 'shop.db';
                if (empty($path)) {
                    throw new Exception("SQLite path cannot be empty");
                }
                
                $dbConfig = [
                    'driver' => 'sqlite',
                    'path' => $path,
                ];
                $logs[] = "→ Selected database: SQLite";
                $logs[] = "→ Database path: " . $path;
            } else {
                $host = $_POST['mysql_host'] ?? '127.0.0.1';
                $port = $_POST['mysql_port'] ?? '3306';
                $dbname = $_POST['mysql_dbname'] ?? 'shop_demo';
                $user = $_POST['mysql_user'] ?? 'root';
                $pass = $_POST['mysql_pass'] ?? '';
                
                $dbConfig = [
                    'driver' => 'mysql',
                    'host' => $host,
                    'port' => $port,
                    'dbname' => $dbname,
                    'user' => $user,
                    'pass' => $pass,
                    'charset' => 'utf8mb4',
                ];
                $logs[] = "→ Selected database: MySQL";
                $logs[] = "→ Server: {$host}:{$port}";
                $logs[] = "→ Schema name: {$dbname}";
            }
            
            // Generate and Write config
            $configFile = __DIR__ . '/../config/config.php';
            $logs[] = "Generating active configuration parameters...";
            
            $mysqlPassEscaped = addslashes($dbConfig['pass'] ?? '');
            $mysqlUserEscaped = addslashes($dbConfig['user'] ?? 'root');
            $mysqlDbnameEscaped = addslashes($dbConfig['dbname'] ?? 'shop_demo');
            $mysqlHostEscaped = addslashes($dbConfig['host'] ?? '127.0.0.1');
            $mysqlPortEscaped = addslashes($dbConfig['port'] ?? '3306');
            $baseUrlEscaped = addslashes($baseUrlVal);
            
            // Write SQLite path properly
            if ($driver === 'sqlite') {
                $sqlitePathCode = "__DIR__ . '/../" . addslashes($dbConfig['path']) . "'";
            } else {
                $sqlitePathCode = "__DIR__ . '/../shop.db'";
            }
            
            $configContent = "<?php\n" .
                "/**\n" .
                " * Demoshop Configuration\n" .
                " * Generated by Web Installer\n" .
                " */\n\n" .
                "return [\n" .
                "    'db' => [\n" .
                "        'driver'   => '{$driver}',\n" .
                "        \n" .
                "        // MySQL settings\n" .
                "        'host'     => '{$mysqlHostEscaped}',\n" .
                "        'port'     => '{$mysqlPortEscaped}',\n" .
                "        'dbname'   => '{$mysqlDbnameEscaped}',\n" .
                "        'user'     => '{$mysqlUserEscaped}',\n" .
                "        'pass'     => '{$mysqlPassEscaped}',\n" .
                "        'charset'  => 'utf8mb4',\n" .
                "        \n" .
                "        // SQLite settings\n" .
                "        'path'     => {$sqlitePathCode},\n" .
                "    ],\n" .
                "    \n" .
                "    // Site settings\n" .
                "    'site' => [\n" .
                "        'base_url' => '{$baseUrlEscaped}',\n" .
                "    ],\n" .
                "    \n" .
                "    // Application settings\n" .
                "    'app' => [\n" .
                "        'debug' => true,\n" .
                "        'log_path' => __DIR__ . '/../logs/app.log',\n" .
                "        'error_log_path' => __DIR__ . '/../logs/error.log',\n" .
                "        'log_retention_days' => 30,\n" .
                "        'server_port' => 8000,\n" .
                "    ],\n" .
                "];\n";
                
            if (!is_writable(dirname($configFile)) && !is_writable($configFile)) {
                throw new Exception("Config directory config/ is not writable.");
            }
            
            if (file_put_contents($configFile, $configContent) === false) {
                throw new Exception("Failed to write config file to " . $configFile);
            }
            $logs[] = "[OK] Configuration successfully committed to 'config/config.php'";
            
            // Set runtime config for database classes
            $logs[] = "Establishing connection to active database...";
            if ($driver === 'sqlite') {
                // Set path absolute for runtime
                $runtimeDbConfig = $dbConfig;
                $runtimeDbConfig['path'] = __DIR__ . '/../' . $dbConfig['path'];
                \App\Core\Database::setRuntimeConfig($runtimeDbConfig);
            } else {
                \App\Core\Database::setRuntimeConfig($dbConfig);
            }
            
            // Get connection, this will auto create database if it doesn't exist for mysql
            $pdo = \App\Core\Database::getConnection();
            $logs[] = "[OK] Database connection verified.";
            
            // Bootstrap a clean container
            $logs[] = "Initializing Core Dependency Injection Container...";
            $newContainer = new \App\Core\Container();
            
            $request = \App\Core\Request::createFromGlobals();
            $newContainer->set(\App\Core\Request::class, fn() => $request);
            
            $servicesFactory = require __DIR__ . '/../config/services.php';
            $appConfig = require $configFile;
            $services = $servicesFactory($appConfig);
            foreach ($services as $id => $factory) {
                $newContainer->set($id, $factory);
            }
            $logs[] = "[OK] DI Container bound successfully.";
            
            // Run Migrations
            $logs[] = "Running Schema Migrations...";
            $migrationService = $newContainer->get(\App\Services\MigrationServiceInterface::class);
            $applied = $migrationService->applyMigrations();
            if (empty($applied)) {
                $logs[] = "→ Schema already matches latest migration migrations.";
            } else {
                foreach ($applied as $mName) {
                    $logs[] = "  → Executed migration: {$mName}";
                }
                $logs[] = "[OK] Applied " . count($applied) . " migrations successfully.";
            }
            
            // Run Seed
            if ($seed) {
                $logs[] = "Executing database seeding...";
                $seedService = $newContainer->get(\App\Services\DatabaseSeedServiceInterface::class);
                $seedService->seed();
                $logs[] = "[OK] Database seeded successfully with demo catalog products & categories.";
                
                // Override default admin user
                if (!empty($adminEmail) && !empty($adminPassword)) {
                    $logs[] = "Configuring administrator account credentials...";
                    $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET email = :email, password_hash = :password WHERE id = 1");
                    $stmt->execute([
                        'email' => $adminEmail,
                        'password' => $passwordHash
                    ]);
                    $logs[] = "[OK] Administrator set to: " . $adminEmail;
                }
            } else {
                // If seed was disabled, create a single custom admin
                $logs[] = "Creating active administrator account...";
                $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
                
                // Double check if tables exist and create user
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                $stmt->execute();
                if ($stmt->fetchColumn() == 0) {
                    $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, is_verified) VALUES ('Admin', :email, :password, 'admin', 1)");
                    $insertStmt->execute([
                        'email' => $adminEmail,
                        'password' => $passwordHash
                    ]);
                    $logs[] = "[OK] Created default Administrator: " . $adminEmail;
                } else {
                    $logs[] = "→ Administrator account already exists.";
                }
            }
            
            $logs[] = "Finalizing installation configurations...";
            
            // Save base_url setting in database
            $logs[] = "Configuring base URL setting in database: '{$baseUrlVal}'";
            $stmt = $pdo->prepare("REPLACE INTO settings (`key`, value) VALUES ('base_url', ?)");
            $stmt->execute([$baseUrlVal]);
            
            $logs[] = "✨ DemoShop successfully installed!";
            echo json_encode(['success' => true, 'logs' => $logs]);
        } catch (Exception $e) {
            $logs[] = "❌ CRITICAL ERROR: " . $e->getMessage();
            echo json_encode(['success' => false, 'logs' => $logs, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// System requirement validation
$reqs = [
    'php' => [
        'name' => 'PHP 8.0 or Greater',
        'desc' => 'Required for modern OOP constructs and attributes.',
        'passed' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'current' => PHP_VERSION,
    ],
    'config_dir' => [
        'name' => 'Config Directory Writeable',
        'desc' => 'Required to save your database credentials.',
        'passed' => is_writable(__DIR__ . '/../config') || (file_exists(__DIR__ . '/../config/config.php') && is_writable(__DIR__ . '/../config/config.php')),
        'current' => is_writable(__DIR__ . '/../config') ? 'Yes' : 'No',
    ],
    'logs_dir' => [
        'name' => 'Logs Directory Writeable',
        'desc' => 'Required for system error logging and diagnostics.',
        'passed' => (is_dir(__DIR__ . '/../logs') && is_writable(__DIR__ . '/../logs')) || is_writable(__DIR__ . '/..'),
        'current' => (is_dir(__DIR__ . '/../logs') && is_writable(__DIR__ . '/../logs')) || is_writable(__DIR__ . '/..') ? 'Yes' : 'No',
    ],
    'uploads_dir' => [
        'name' => 'Uploads Directory Writeable',
        'desc' => 'Required for active product image files uploads.',
        'passed' => is_writable(__DIR__ . '/../public') || (is_dir(__DIR__ . '/../public/uploads') && is_writable(__DIR__ . '/../public/uploads')),
        'current' => (is_dir(__DIR__ . '/../public/uploads') && is_writable(__DIR__ . '/../public/uploads')) || is_writable(__DIR__ . '/../public') ? 'Yes' : 'No',
    ],
];

$allPassed = true;
foreach ($reqs as $r) {
    if (!$r['passed']) {
        $allPassed = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up DemoShop</title>
    <!-- Outfit & Inter Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: hsl(222, 47%, 10%);
            --card-bg: rgba(15, 23, 42, 0.65);
            --border-color: rgba(255, 255, 255, 0.08);
            --primary: hsl(210, 100%, 56%);
            --primary-glow: hsla(210, 100%, 56%, 0.35);
            --secondary: hsl(280, 80%, 60%);
            --success: hsl(142, 70%, 45%);
            --success-glow: hsla(142, 70%, 45%, 0.25);
            --warning: hsl(38, 92%, 50%);
            --danger: hsl(0, 84%, 60%);
            --text-main: hsl(210, 40%, 98%);
            --text-muted: hsl(215, 20%, 65%);
            --font-display: 'Outfit', -apple-system, sans-serif;
            --font-sans: 'Inter', -apple-system, sans-serif;
            --glass-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: var(--font-sans);
        }

        body {
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 10% 20%, hsla(210, 100%, 56%, 0.12) 0px, transparent 50%),
                radial-gradient(at 90% 80%, hsla(280, 80%, 60%, 0.1) 0px, transparent 50%);
            background-attachment: fixed;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            overflow-x: hidden;
        }

        /* Glassmorphism Card Container */
        .wizard-container {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            width: 100%;
            max-width: 680px;
            box-shadow: var(--glass-shadow);
            overflow: hidden;
            position: relative;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Card Header */
        .wizard-header {
            padding: 40px 40px 24px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
            position: relative;
        }

        .logo-wrap {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            margin: 0 auto 16px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px var(--primary-glow);
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 32px;
            color: white;
            position: relative;
        }

        .logo-wrap::after {
            content: '';
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 18px;
            z-index: -1;
            opacity: 0.5;
            filter: blur(8px);
        }

        h1 {
            font-family: var(--font-display);
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #ffffff, #c7d2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 15px;
            font-weight: 400;
        }

        /* Step Progress Dots */
        .step-progress {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 24px;
        }

        .progress-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            transition: all 0.3s ease;
        }

        .progress-dot.active {
            background: var(--primary);
            box-shadow: 0 0 10px var(--primary);
            width: 24px;
            border-radius: 4px;
        }

        .progress-dot.passed {
            background: var(--success);
        }

        /* Wizard Views (Hidden by Default) */
        .wizard-view {
            display: none;
            padding: 40px;
            animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .wizard-view.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Requirements Checker styling */
        .req-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 30px;
        }

        .req-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: border-color 0.2s ease;
        }

        .req-item:hover {
            border-color: rgba(255, 255, 255, 0.1);
        }

        .req-info h3 {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .req-info p {
            font-size: 13px;
            color: var(--text-muted);
        }

        .badge {
            font-size: 12px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 30px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: hsl(142, 70%, 55%);
            border: 1px solid rgba(16, 185, 129, 0.2);
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.05);
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.1);
            color: hsl(0, 84%, 65%);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Database Selector Tab */
        .tab-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 6px;
            margin-bottom: 30px;
            position: relative;
        }

        .tab-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 14px;
            padding: 12px;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .tab-btn.active {
            color: white;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        /* Forms Layout */
        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 12px 16px;
            color: white;
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        /* Checkboxes styling */
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 14px;
            color: var(--text-main);
            margin-top: 10px;
            user-select: none;
        }

        .checkbox-container input {
            display: none;
        }

        .custom-checkbox {
            width: 20px;
            height: 20px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.03);
            transition: all 0.2s ease;
        }

        .checkbox-container input:checked + .custom-checkbox {
            background: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 0 10px var(--primary-glow);
        }

        .checkbox-container input:checked + .custom-checkbox::after {
            content: '✓';
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        /* Beautiful Dynamic Action Buttons */
        .action-area {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            border: none;
            outline: none;
            width: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 4px 15px var(--primary-glow);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px var(--primary-glow);
            filter: brightness(1.1);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        /* Alert Boxes */
        .alert {
            padding: 16px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 24px;
            display: none;
            border: 1px solid transparent;
            animation: fadeIn 0.3s ease;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.08);
            border-color: rgba(239, 68, 68, 0.2);
            color: hsl(0, 84%, 70%);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.08);
            border-color: rgba(16, 185, 129, 0.2);
            color: hsl(142, 70%, 70%);
        }

        /* Installation Progress Terminal */
        .terminal-panel {
            background: #020617;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            font-family: 'SFMono-Regular', Consolas, Menlo, monospace;
            padding: 20px;
            height: 220px;
            overflow-y: auto;
            color: #38bdf8;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 24px;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.8);
            scrollbar-width: thin;
        }

        .terminal-panel::-webkit-scrollbar {
            width: 6px;
        }

        .terminal-panel::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.1);
            border-radius: 3px;
        }

        .terminal-line {
            white-space: pre-wrap;
            margin-bottom: 6px;
            opacity: 0.9;
        }

        .terminal-line.success {
            color: #4ade80;
        }

        .terminal-line.error {
            color: #f87171;
            font-weight: bold;
        }

        /* Dynamic Progress Bar */
        .progress-bar-container {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            height: 8px;
            overflow: hidden;
            width: 100%;
            margin-bottom: 10px;
        }

        .progress-bar-fill {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            width: 0%;
            height: 100%;
            border-radius: 20px;
            transition: width 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 0 8px var(--primary-glow);
        }

        /* Success View Animations */
        .success-illustration {
            text-align: center;
            margin-bottom: 30px;
        }

        .checkmark-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--success-glow);
            border: 2px solid var(--success);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 0 20px var(--success-glow);
            position: relative;
        }

        .checkmark-icon {
            font-size: 40px;
            color: var(--success);
            line-height: 80px;
            animation: bounceIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes bounceIn {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }

        .success-title {
            text-align: center;
            margin-bottom: 24px;
        }

        .success-title h2 {
            font-family: var(--font-display);
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
        }

        .success-title p {
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Customization & Info Panels */
        .info-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .info-card p {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 12px;
        }

        .admin-creds-list {
            margin-top: 10px;
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
            padding: 12px 16px;
            border-left: 3px solid var(--primary);
        }

        .cred-item {
            font-size: 13px;
            margin-bottom: 6px;
            font-family: 'SFMono-Regular', Consolas, monospace;
            display: flex;
            justify-content: space-between;
        }

        .cred-item:last-child {
            margin-bottom: 0;
        }

        .cred-item span:first-child {
            color: var(--text-muted);
        }

        .cred-item span:last-child {
            color: white;
            font-weight: 600;
        }

        .pulse-loader {
            display: inline-block;
            width: 14px;
            height: 14px;
            background: white;
            border-radius: 50%;
            animation: pulse 1.2s infinite ease-in-out;
        }

        @keyframes pulse {
            0% { transform: scale(0.6); opacity: 0.4; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(0.6); opacity: 0.4; }
        }

        /* Spinner for connection test */
        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Helper Styles */
        .flex-row {
            display: flex;
            gap: 16px;
        }
        
        .flex-grow {
            flex-grow: 1;
        }
        
        .troubleshoot-box {
            background: rgba(239, 68, 68, 0.03);
            border: 1px solid rgba(239, 68, 68, 0.15);
            border-radius: 12px;
            padding: 16px 20px;
            font-size: 13.5px;
            color: hsl(0, 84%, 75%);
            margin-bottom: 24px;
            line-height: 1.5;
        }

        /* Password Visibility Toggle */
        .password-input-container {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .password-input-container input[type="password"],
        .password-input-container input[type="text"] {
            padding-right: 48px;
        }

        .password-toggle-btn {
            position: absolute;
            right: 14px;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.25s ease, transform 0.2s ease;
            z-index: 10;
            outline: none;
        }

        .password-toggle-btn:hover {
            color: var(--primary);
            transform: scale(1.1);
        }

        .password-toggle-btn:active {
            transform: scale(0.95);
        }

        .password-toggle-btn svg {
            width: 20px;
            height: 20px;
        }
    </style>
</head>
<body>

    <div class="wizard-container">
        <!-- Header -->
        <div class="wizard-header">
            <div class="logo-wrap">D</div>
            <h1 id="wizard-title">Configure DemoShop</h1>
            <p class="subtitle" id="wizard-subtitle">Onboarding Setup Wizard</p>
            
            <!-- Step Indicators -->
            <div class="step-progress">
                <div class="progress-dot active" id="dot-1"></div>
                <div class="progress-dot" id="dot-2"></div>
                <div class="progress-dot" id="dot-3"></div>
                <div class="progress-dot" id="dot-4"></div>
                <div class="progress-dot" id="dot-5"></div>
            </div>
        </div>

        <!-- VIEW 1: System Pre-Checks -->
        <div class="wizard-view active" id="view-1">
            <div class="req-list">
                <?php foreach ($reqs as $key => $r): ?>
                <div class="req-item">
                    <div class="req-info">
                        <h3><?php echo htmlspecialchars($r['name']); ?></h3>
                        <p><?php echo htmlspecialchars($r['desc']); ?></p>
                    </div>
                    <div>
                        <?php if ($r['passed']): ?>
                            <span class="badge badge-success">Passed (<?php echo htmlspecialchars($r['current']); ?>)</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Failed (<?php echo htmlspecialchars($r['current']); ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$allPassed): ?>
                <div class="troubleshoot-box">
                    <strong>⚠️ Setup Blocked:</strong> Write permissions are required to generate configuration and logs. Run the following command in your terminal to fix directory permissions:<br>
                    <code style="background: rgba(0,0,0,0.3); padding: 4px 6px; border-radius: 4px; display: inline-block; margin-top: 8px; font-family: monospace; color: white;">chmod -R 775 config logs public/uploads</code>
                </div>
            <?php endif; ?>

            <div class="action-area">
                <div></div>
                <button class="btn btn-primary" id="btn-to-step2" <?php echo !$allPassed ? 'disabled' : ''; ?>>
                    Configure Database &nbsp; ➜
                </button>
            </div>
        </div>

        <!-- VIEW 2: Database Settings -->
        <div class="wizard-view" id="view-2">
            <!-- Tabs SQLite / MySQL -->
            <div class="tab-selector">
                <button type="button" class="tab-btn active" id="tab-sqlite">
                    💾 SQLite
                </button>
                <button type="button" class="tab-btn" id="tab-mysql">
                    ⚡ MySQL
                </button>
            </div>

            <!-- DB Errors Alert Box -->
            <div class="alert alert-error" id="db-alert"></div>

            <form id="db-form">
                <!-- Hidden driver state -->
                <input type="hidden" name="driver" id="db-driver" value="sqlite">

                <!-- SQLite Pane -->
                <div id="pane-sqlite">
                    <div class="form-group">
                        <label for="sqlite_path">Database File Path</label>
                        <input type="text" id="sqlite_path" name="sqlite_path" value="shop.db" placeholder="e.g. shop.db">
                        <p style="font-size:12px; color: var(--text-muted); margin-top:6px;">
                            The file will be automatically created in your workspace directory root.
                        </p>
                    </div>
                </div>

                <!-- MySQL Pane (Hidden by Default) -->
                <div id="pane-mysql" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="mysql_host">Host IP or Domain</label>
                            <input type="text" id="mysql_host" name="mysql_host" value="127.0.0.1">
                        </div>
                        <div class="form-group">
                            <label for="mysql_port">Port</label>
                            <input type="text" id="mysql_port" name="mysql_port" value="3306">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="mysql_dbname">Database Name</label>
                        <input type="text" id="mysql_dbname" name="mysql_dbname" value="shop_demo">
                        <p style="font-size:12px; color: var(--text-muted); margin-top:6px;">
                            If the schema does not exist, the installer will attempt to create it.
                        </p>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="mysql_user">Username</label>
                            <input type="text" id="mysql_user" name="mysql_user" value="root">
                        </div>
                        <div class="form-group">
                            <label for="mysql_pass">Password</label>
                            <input type="password" id="mysql_pass" name="mysql_pass" placeholder="Database password">
                        </div>
                    </div>
                </div>

                <div class="action-area">
                    <button type="button" class="btn btn-secondary flex-grow" style="max-width:180px;" id="btn-back-to-step1">
                        ⬅ Back
                    </button>
                    <div class="flex-row flex-grow" style="justify-content: flex-end;">
                        <button type="button" class="btn btn-secondary" style="max-width:180px;" id="btn-test-connection">
                            Test Connection
                        </button>
                        <button type="button" class="btn btn-primary" style="max-width:140px;" id="btn-to-step3" disabled>
                            Next ➜
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- VIEW 3: Customization & Admin account -->
        <div class="wizard-view" id="view-3">
            <div class="info-card">
                <p>Configure the initial environment and create the administrator user account for the DemoShop admin panel.</p>
                <label class="checkbox-container">
                    <input type="checkbox" id="seed-checkbox" checked>
                    <span class="custom-checkbox"></span>
                    <span>Seed Demo Database Catalog (Categories, Products, Variants)</span>
                </label>
            </div>

            <div class="form-group">
                <label for="base_url_mode">Web Root / URL Access Mode</label>
                <select id="base_url_mode" name="base_url_mode" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-color); font-size: 14px; margin-top: 6px; box-sizing: border-box;">
                    <option value="clean" selected>Standard (Web server root points to /public subdirectory - clean URLs)</option>
                    <option value="restricted">Restricted Host (Web server root points to project root - URLs start with /public)</option>
                </select>
                <p style="font-size:12px; color: var(--text-muted); margin-top:6px;" id="base_url_desc">
                    <strong>Standard Option:</strong> Keeps URLs clean (e.g. <code>/products</code>). Assumes your web server root points directly to the <code>/public</code> subdirectory.
                </p>
            </div>

            <div class="form-group">
                <label for="admin_email">Administrator Email</label>
                <input type="email" id="admin_email" name="admin_email" value="admin@shop.local">
            </div>

            <div class="form-group">
                <label for="admin_password">Administrator Password</label>
                <div class="password-input-container">
                    <input type="password" id="admin_password" name="admin_password" value="password">
                    <button type="button" class="password-toggle-btn" id="toggle-admin-password" aria-label="Toggle password visibility">
                        <!-- Eye Icon -->
                        <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <!-- Eye Slash Icon -->
                        <svg class="eye-slash-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                <p style="font-size:12px; color: var(--text-muted); margin-top:6px;">
                    Keep note of this password. It is required to log into the Admin portal (`/admin`).
                </p>
            </div>

            <div class="action-area">
                <button class="btn btn-secondary" style="max-width:180px;" id="btn-back-to-step2">
                    ⬅ Back
                </button>
                <button class="btn btn-primary" style="max-width:200px;" id="btn-start-installation">
                    Install DemoShop 🚀
                </button>
            </div>
        </div>

        <!-- VIEW 4: Installation Progress -->
        <div class="wizard-view" id="view-4">
            <div class="progress-bar-container">
                <div class="progress-bar-fill" id="install-progress"></div>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--text-muted); margin-bottom: 20px;">
                <span id="install-step-text">Starting installation...</span>
                <span id="install-percent">0%</span>
            </div>

            <div class="terminal-panel" id="terminal-console">
                <div class="terminal-line">[SYSTEM] Ready for installation pipeline bootstrap.</div>
            </div>

            <div class="action-area" style="justify-content:center;">
                <button class="btn btn-primary" style="max-width:240px; display:none;" id="btn-retry-install">
                    🔄 Retry Installation
                </button>
            </div>
        </div>

        <!-- VIEW 5: Success Screen -->
        <div class="wizard-view" id="view-5">
            <div class="success-illustration">
                <div class="checkmark-circle">
                    <span class="checkmark-icon">✓</span>
                </div>
            </div>

            <div class="success-title">
                <h2>Congratulations!</h2>
                <p>DemoShop has been successfully installed and configured.</p>
            </div>

            <div class="info-card">
                <p>Your Admin Panel is fully configured and ready for management.</p>
                <div class="admin-creds-list">
                    <div class="cred-item">
                        <span>Portal URL</span>
                        <span>/admin</span>
                    </div>
                    <div class="cred-item">
                        <span>Email</span>
                        <span id="success-email">admin@shop.local</span>
                    </div>
                    <div class="cred-item">
                        <span>Password</span>
                        <span id="success-password">password</span>
                    </div>
                </div>
            </div>

            <div class="action-area" style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <a href="/" class="btn btn-secondary" style="text-decoration:none;">
                    🛍️ Go to Storefront
                </a>
                <a href="/login" class="btn btn-primary" style="text-decoration:none;">
                    ⚙️ Log into Admin
                </a>
            </div>
        </div>
    </div>

    <!-- Wizard Javascript Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Views
            const views = [
                document.getElementById('view-1'),
                document.getElementById('view-2'),
                document.getElementById('view-3'),
                document.getElementById('view-4'),
                document.getElementById('view-5')
            ];

            // Progress Dots
            const dots = [
                document.getElementById('dot-1'),
                document.getElementById('dot-2'),
                document.getElementById('dot-3'),
                document.getElementById('dot-4'),
                document.getElementById('dot-5')
            ];

            // Headers texts
            const titles = [
                "Configure DemoShop",
                "Database Credentials",
                "Environment Customization",
                "Installing Application",
                "Installation Succeeded!"
            ];

            const subtitles = [
                "System Requirements Checklist",
                "Configure active storage drivers",
                "Advanced catalog parameters",
                "Executing migrations and seeder",
                "Application is ready for production"
            ];

            // Global Wizard state
            let currentStep = 1;
            let dbTested = false;

            function goToStep(step) {
                // Bounds checking
                if (step < 1 || step > 5) return;
                
                // Toggle active view
                views.forEach((v, index) => {
                    if (index + 1 === step) {
                        v.classList.add('active');
                    } else {
                        v.classList.remove('active');
                    }
                });

                // Toggle dots
                dots.forEach((d, index) => {
                    d.classList.remove('active', 'passed');
                    if (index + 1 === step) {
                        d.classList.add('active');
                    } else if (index + 1 < step) {
                        d.classList.add('passed');
                    }
                });

                // Update headers
                document.getElementById('wizard-title').innerText = titles[step - 1];
                document.getElementById('wizard-subtitle').innerText = subtitles[step - 1];
                
                currentStep = step;
            }

            // --- STEP 1 INTERACTION ---
            document.getElementById('btn-to-step2').addEventListener('click', () => {
                goToStep(2);
            });

            // --- STEP 2 INTERACTION (Database) ---
            const tabSqlite = document.getElementById('tab-sqlite');
            const tabMysql = document.getElementById('tab-mysql');
            const paneSqlite = document.getElementById('pane-sqlite');
            const paneMysql = document.getElementById('pane-mysql');
            const dbDriver = document.getElementById('db-driver');
            const btnToStep3 = document.getElementById('btn-to-step3');
            const dbAlert = document.getElementById('db-alert');

            tabSqlite.addEventListener('click', () => {
                tabSqlite.classList.add('active');
                tabMysql.classList.remove('active');
                paneSqlite.style.display = 'block';
                paneMysql.style.display = 'none';
                dbDriver.value = 'sqlite';
                dbAlert.style.display = 'none';
                
                // Allow proceeding on SQLite instantly since standard path is local
                btnToStep3.removeAttribute('disabled');
            });

            tabMysql.addEventListener('click', () => {
                tabMysql.classList.add('active');
                tabSqlite.classList.remove('active');
                paneMysql.style.display = 'block';
                paneSqlite.style.display = 'none';
                dbDriver.value = 'mysql';
                dbAlert.style.display = 'none';
                
                // Require connection test for MySQL
                if (!dbTested) {
                    btnToStep3.setAttribute('disabled', 'true');
                } else {
                    btnToStep3.removeAttribute('disabled');
                }
            });

            // Input listener on MySQL to reset tested status on credential edit
            const mysqlInputs = paneMysql.querySelectorAll('input');
            mysqlInputs.forEach(input => {
                input.addEventListener('input', () => {
                    if (dbDriver.value === 'mysql') {
                        dbTested = false;
                        btnToStep3.setAttribute('disabled', 'true');
                    }
                });
            });

            // Test connection AJAX
            const btnTestConn = document.getElementById('btn-test-connection');
            btnTestConn.addEventListener('click', function() {
                btnTestConn.setAttribute('disabled', 'true');
                btnTestConn.innerHTML = '<span class="spinner"></span> &nbsp; Connecting...';
                dbAlert.style.display = 'none';

                const formData = new FormData(document.getElementById('db-form'));

                fetch('/?action=test_connection', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    btnTestConn.removeAttribute('disabled');
                    btnTestConn.innerText = 'Test Connection';

                    if (data.success) {
                        dbAlert.className = 'alert alert-success';
                        dbAlert.innerHTML = '<strong>Connection Successful!</strong> Database credentials are correct.';
                        dbAlert.style.display = 'block';
                        dbTested = true;
                        btnToStep3.removeAttribute('disabled');
                    } else {
                        dbAlert.className = 'alert alert-error';
                        dbAlert.innerHTML = '<strong>Connection Failed:</strong> ' + data.message;
                        dbAlert.style.display = 'block';
                        dbTested = false;
                        btnToStep3.setAttribute('disabled', 'true');
                    }
                })
                .catch(err => {
                    btnTestConn.removeAttribute('disabled');
                    btnTestConn.innerText = 'Test Connection';
                    dbAlert.className = 'alert alert-error';
                    dbAlert.innerHTML = '<strong>Request Error:</strong> Could not connect to system installer backend.';
                    dbAlert.style.display = 'block';
                });
            });

            // SQLite default path should enable Step 3 button instantly
            btnToStep3.removeAttribute('disabled');

            document.getElementById('btn-back-to-step1').addEventListener('click', () => {
                goToStep(1);
            });

            document.getElementById('btn-to-step3').addEventListener('click', () => {
                goToStep(3);
            });

            // --- STEP 3 INTERACTION (Customization) ---
            document.getElementById('btn-back-to-step2').addEventListener('click', () => {
                goToStep(2);
            });

            // Base URL Mode Description toggle
            const baseUrlModeSelect = document.getElementById('base_url_mode');
            const baseUrlDesc = document.getElementById('base_url_desc');
            if (baseUrlModeSelect && baseUrlDesc) {
                baseUrlModeSelect.addEventListener('change', function() {
                    if (this.value === 'clean') {
                        baseUrlDesc.innerHTML = '<strong>Standard Option:</strong> Keeps URLs clean (e.g. <code>/products</code>). Assumes your web server root points directly to the <code>/public</code> subdirectory.';
                    } else {
                        baseUrlDesc.innerHTML = '<strong>Restricted Host Option:</strong> Assumes your host forces the project root as the web root. A secure <code>index.php</code> wrapper will intercept requests in the root and routes will start with <code>/public/...</code> (e.g. <code>/public/products</code>). Assets will load securely without symlinks.';
                    }
                });
            }

            // --- STEP 4 INSTALLATION EXECUTION ---
            const btnStart = document.getElementById('btn-start-installation');
            btnStart.addEventListener('click', function() {
                goToStep(4);
                runInstallation();
            });

            const consoleElement = document.getElementById('terminal-console');
            
            function addLog(text, type = '') {
                const line = document.createElement('div');
                line.className = 'terminal-line ' + type;
                line.innerText = text;
                consoleElement.appendChild(line);
                consoleElement.scrollTop = consoleElement.scrollHeight;
            }

            function runInstallation() {
                const fill = document.getElementById('install-progress');
                const percentText = document.getElementById('install-percent');
                const stepText = document.getElementById('install-step-text');
                const retryBtn = document.getElementById('btn-retry-install');
                
                retryBtn.style.display = 'none';
                consoleElement.innerHTML = '';
                
                // 1. Initial State
                fill.style.width = '10%';
                percentText.innerText = '10%';
                stepText.innerText = 'Initializing configuration...';
                addLog('[SYSTEM] Bootstrapping installer scripts...', '');

                const driver = document.getElementById('db-driver').value;
                const seed = document.getElementById('seed-checkbox').checked;
                const adminEmail = document.getElementById('admin_email').value;
                const adminPassword = document.getElementById('admin_password').value;

                // Form details
                const bodyData = new FormData();
                bodyData.append('driver', driver);
                bodyData.append('seed', seed ? 'true' : 'false');
                bodyData.append('admin_email', adminEmail);
                bodyData.append('admin_password', adminPassword);
                bodyData.append('base_url_mode', document.getElementById('base_url_mode').value);

                if (driver === 'sqlite') {
                    bodyData.append('sqlite_path', document.getElementById('sqlite_path').value);
                } else {
                    bodyData.append('mysql_host', document.getElementById('mysql_host').value);
                    bodyData.append('mysql_port', document.getElementById('mysql_port').value);
                    bodyData.append('mysql_dbname', document.getElementById('mysql_dbname').value);
                    bodyData.append('mysql_user', document.getElementById('mysql_user').value);
                    bodyData.append('mysql_pass', document.getElementById('mysql_pass').value);
                }

                // Smooth progress transitions simulated alongside backend actions
                let progress = 10;
                const interval = setInterval(() => {
                    if (progress < 85) {
                        progress += Math.floor(Math.random() * 8) + 2;
                        if (progress > 85) progress = 85;
                        fill.style.width = progress + '%';
                        percentText.innerText = progress + '%';
                        
                        if (progress > 20 && progress < 45) {
                            stepText.innerText = 'Writing configuration properties...';
                        } else if (progress >= 45 && progress < 70) {
                            stepText.innerText = 'Running schema migrations...';
                        } else if (progress >= 70) {
                            stepText.innerText = seed ? 'Seeding catalog products...' : 'Creating Administrator account...';
                        }
                    }
                }, 350);

                // Make the install call
                fetch('/?action=install', {
                    method: 'POST',
                    body: bodyData
                })
                .then(res => res.json())
                .then(data => {
                    clearInterval(interval);
                    
                    if (data.logs && data.logs.length > 0) {
                        data.logs.forEach(log => {
                            if (log.startsWith('[OK]')) {
                                addLog(log, 'success');
                            } else if (log.startsWith('❌') || log.startsWith('[ERROR]')) {
                                addLog(log, 'error');
                            } else {
                                addLog(log, '');
                            }
                        });
                    }

                    if (data.success) {
                        fill.style.width = '100%';
                        percentText.innerText = '100%';
                        stepText.innerText = 'Installation completed!';
                        
                        // Set credentials in success pane
                        document.getElementById('success-email').innerText = adminEmail;
                        document.getElementById('success-password').innerText = adminPassword;

                        setTimeout(() => {
                            goToStep(5);
                        }, 1200);
                    } else {
                        stepText.innerText = 'Installation failed!';
                        addLog('❌ ERROR: Configuration failed. Check directory permissions or MySQL credentials.', 'error');
                        retryBtn.style.display = 'block';
                    }
                })
                .catch(err => {
                    clearInterval(interval);
                    stepText.innerText = 'Request error!';
                    addLog('❌ REQUEST ERROR: Connection timed out or server failed: ' + err.message, 'error');
                    retryBtn.style.display = 'block';
                });
            }

            // Retry install trigger
            document.getElementById('btn-retry-install').addEventListener('click', () => {
                runInstallation();
            });

            // Password visibility toggle logic
            const adminPasswordInput = document.getElementById('admin_password');
            const toggleAdminPasswordBtn = document.getElementById('toggle-admin-password');
            if (adminPasswordInput && toggleAdminPasswordBtn) {
                const eyeIcon = toggleAdminPasswordBtn.querySelector('.eye-icon');
                const eyeSlashIcon = toggleAdminPasswordBtn.querySelector('.eye-slash-icon');
                
                toggleAdminPasswordBtn.addEventListener('click', function() {
                    if (adminPasswordInput.type === 'password') {
                        adminPasswordInput.type = 'text';
                        eyeIcon.style.display = 'none';
                        eyeSlashIcon.style.display = 'block';
                    } else {
                        adminPasswordInput.type = 'password';
                        eyeIcon.style.display = 'block';
                        eyeSlashIcon.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>
