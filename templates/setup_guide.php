<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup Required - DemoShop</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 800px; margin: 40px auto; padding: 0 20px; background: #f8f9fa; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 30px; border-top: 5px solid #007bff; }
        h1 { color: #212529; margin-top: 0; }
        h2 { color: #495057; font-size: 1.2rem; margin-top: 25px; }
        .error-box { background: #fff1f0; border: 1px solid #ffa39e; color: #cf1322; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-family: monospace; font-size: 0.9rem; }
        code { background: #f1f1f1; padding: 2px 4px; border-radius: 3px; font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .step { margin-bottom: 20px; }
        .step-number { display: inline-block; width: 24px; height: 24px; background: #007bff; color: #fff; text-align: center; border-radius: 50%; line-height: 24px; font-size: 14px; font-weight: bold; margin-right: 10px; }
        .success { color: #52c41a; font-weight: bold; }
        .warning { color: #faad14; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 0.85rem; color: #6c757d; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Welcome to DemoShop</h1>
        <p>It looks like the application is not fully set up yet. Follow these steps to get your demonstration store running.</p>

        <?php if (isset($error_message)): ?>
        <div class="error-box">
            <strong>Database Error:</strong><br>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <div class="step">
            <h2><span class="step-number">1</span> Configuration</h2>
            <?php if (file_exists(__DIR__ . '/../config/config.php')): ?>
                <p><span class="success">✓</span> <code>config/config.php</code> found.</p>
                <p><strong>Current Database Configuration:</strong></p>
                <pre><?php 
                    $dbConfig = defined('DB_CONFIG') ? DB_CONFIG : [];
                    if ($dbConfig) {
                        if ($dbConfig['driver'] === 'sqlite') {
                            echo "Driver: SQLite\nPath: " . $dbConfig['path'];
                        } else {
                            echo "Driver: MySQL\nHost: " . $dbConfig['host'] . "\nDatabase: " . $dbConfig['dbname'] . "\nUser: " . $dbConfig['user'];
                        }
                    } else {
                        echo "No database configuration found.";
                    }
                ?></pre>
            <?php else: ?>
                <p><span class="warning">!</span> <code>config/config.php</code> missing.</p>
                <p>Copy <code>config/config.example.php</code> to <code>config/config.php</code> and update your database credentials.</p>
                <pre>cp config/config.example.php config/config.php</pre>
            <?php endif; ?>
        </div>

        <div class="step">
            <h2><span class="step-number">2</span> Database Migrations</h2>
            <p>Initialize the database schema by running the migrations via the CLI:</p>
            <pre>php cli/console.php migrate</pre>
        </div>

        <div class="step">
            <h2><span class="step-number">3</span> Seed Data</h2>
            <p>Populate the store with categories, products, and a default admin user:</p>
            <pre>php cli/console.php db:seed</pre>
            <p><em>Default Admin: admin@example.com / password</em></p>
        </div>

        <div class="step">
            <h2><span class="step-number">4</span> Permissions</h2>
            <p>Ensure the following directories are writable by the web server:</p>
            <ul>
                <li><code>logs/</code></li>
                <li><code>public/uploads/</code></li>
                <li>The directory containing your SQLite database (if using SQLite)</li>
            </ul>
        </div>

        <p>Once you've completed these steps, <a href="/">refresh this page</a>.</p>
    </div>
    <div class="footer">
        DemoShop &bull; Built with Vanilla PHP 8
    </div>
</body>
</html>
