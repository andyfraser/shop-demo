<?php

/**
 * Migration wrapper script.
 * 
 * Note: This script is deprecated. Please use the centralized CLI console instead:
 * php cli/console.php migrate
 */

echo "DEPRECATED: migrate.php is deprecated. Using 'php cli/console.php migrate' instead.\n\n";

$mode = $argv[1] ?? 'up';
$command = $mode === '--rollback' ? 'migrate:rollback' : 'migrate';

passthru("php cli/console.php $command");
