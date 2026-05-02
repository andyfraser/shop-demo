<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\SecurityServiceInterface;
use App\Services\BackupServiceInterface;
use Exception;

class AdminBackupController {
    public function __construct(
        private Renderer $renderer,
        private SecurityServiceInterface $securityService,
        private BackupServiceInterface $backupService,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function index() {
        $this->renderer->adminRender('backup', [
            'page_title' => 'Database Backup & Restore',
            'active'     => 'backup',
            'flash_msg'  => flash('msg'),
            'error_msg'  => flash('error'),
        ]);
    }

    public function download() {
        try {
            $backup = $this->backupService->export();
            
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $backup['mime']);
            header('Content-Disposition: attachment; filename="' . $backup['filename'] . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($backup['path']));
            
            $this->logger->info("Admin downloaded database backup");
            readfile($backup['path']);
            
            if ($backup['temp']) {
                @unlink($backup['path']);
            }
            exit;
        } catch (Exception $e) {
            flash('error', 'Backup failed: ' . $e->getMessage());
            redirect('/admin/backup');
        }
    }

    public function restore() {
        $file = $_FILES['backup_file'] ?? null;
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            flash('error', 'Please select a file to restore.');
            redirect('/admin/backup');
        }

        try {
            if ($this->backupService->import($file)) {
                $this->logger->info("Admin restored database from backup file: {filename}", ['filename' => $file['name']]);
                flash('msg', 'Database restored successfully.');
            } else {
                flash('error', 'Restore failed.');
            }
        } catch (Exception $e) {
            $this->logger->error("Admin database restore failed: {error}", ['error' => $e->getMessage()]);
            flash('error', 'Restore failed: ' . $e->getMessage());
        }

        redirect('/admin/backup');
    }
}
