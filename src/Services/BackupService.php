<?php
namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

class BackupService implements BackupServiceInterface {
    public function __construct(private PDO $db) {}

    public function export(): array {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $timestamp = date('Ymd_His');

        if ($driver === 'sqlite') {
            $dbPath = Database::getSqlitePath();
            
            if (!file_exists($dbPath)) {
                throw new Exception("SQLite database file not found at: " . $dbPath);
            }

            return [
                'path' => $dbPath,
                'mime' => 'application/x-sqlite3',
                'filename' => "shop_backup_{$timestamp}.db",
                'temp' => false
            ];
        } else if ($driver === 'mysql') {
            $sql = "-- Shop Demo Backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            $tables = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                // Drop table
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                
                // Create table
                $createRes = $this->db->query("SHOW CREATE TABLE `{$table}`")->fetch();
                $sql .= $createRes['Create Table'] . ";\n\n";

                // Inserts
                $rows = $this->db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
                if ($rows) {
                    $sql .= "INSERT INTO `{$table}` VALUES \n";
                    $insertRows = [];
                    foreach ($rows as $row) {
                        $values = array_map(function($val) {
                            if ($val === null) return 'NULL';
                            return $this->db->quote($val);
                        }, array_values($row));
                        $insertRows[] = "(" . implode(', ', $values) . ")";
                    }
                    $sql .= implode(",\n", $insertRows) . ";\n\n";
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            $tempFile = tempnam(sys_get_temp_dir(), 'shop_bak_');
            file_put_contents($tempFile, $sql);

            return [
                'path' => $tempFile,
                'mime' => 'application/sql',
                'filename' => "shop_backup_{$timestamp}.sql",
                'temp' => true
            ];
        }

        throw new Exception("Unsupported driver for export: " . $driver);
    }

    public function import(array $file): bool {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload failed with error code: " . $file['error']);
        }

        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

        if ($driver === 'sqlite') {
            if ($extension !== 'db' && $extension !== 'sqlite') {
                throw new Exception("Invalid file extension for SQLite. Expected .db or .sqlite");
            }

            // Simple SQLite validation: check first 16 bytes
            $handle = fopen($file['tmp_name'], 'rb');
            $header = fread($handle, 16);
            fclose($handle);
            if ($header !== "SQLite format 3\0") {
                throw new Exception("The uploaded file is not a valid SQLite 3 database.");
            }

            $dbPath = Database::getSqlitePath();

            // Close PDO connection to release lock
            Database::closeConnection();
            
            // For good measure, try to trigger GC
            gc_collect_cycles();

            if (!copy($file['tmp_name'], $dbPath)) {
                throw new Exception("Failed to overwrite database file. Check permissions.");
            }

            return true;
        } else if ($driver === 'mysql') {
            if ($extension !== 'sql') {
                throw new Exception("Invalid file extension for MySQL. Expected .sql");
            }

            $sql = file_get_contents($file['tmp_name']);
            if (!$sql) {
                throw new Exception("Could not read uploaded SQL file.");
            }

            $this->db->exec("SET FOREIGN_KEY_CHECKS=0");
            
            // Execute the SQL. Since it might contain multiple statements, we can't just use exec() 
            // if it has many statements on some PDO drivers, but MySQL usually allows it.
            // However, it's safer to split or use a loop if possible, but for a backup file 
            // produced by our export(), it should be fine.
            $this->db->exec($sql);
            
            $this->db->exec("SET FOREIGN_KEY_CHECKS=1");

            return true;
        }

        return false;
    }
}
