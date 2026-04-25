<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\SecurityService;
use App\Services\BackupService;
use Exception;

class AdminBackupController {
    private Renderer $renderer;
    private SecurityService $securityService;
    private BackupService $backupService;

    public function __construct(Renderer $renderer, SecurityService $securityService, BackupService $backupService) {
        $this->renderer = $renderer;
        $this->securityService = $securityService;
        $this->backupService = $backupService;
    }

    public function index() {
        $this->renderer->adminRender('backup', [
            'page_title' => 'Database Backup & Restore',
            'active'     => 'backup',
            'flash_msg'  => flash('msg'),
            'error_msg'  => flash('error'),
        ]);
    }

    public function download() {
        $this->securityService->verifyCsrf();

        try {
            $backup = $this->backupService->export();
            
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $backup['mime']);
            header('Content-Disposition: attachment; filename="' . $backup['filename'] . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($backup['path']));
            
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
        $this->securityService->verifyCsrf();

        $file = $_FILES['backup_file'] ?? null;
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            flash('error', 'Please select a file to restore.');
            redirect('/admin/backup');
        }

        try {
            if ($this->backupService->import($file)) {
                flash('msg', 'Database restored successfully.');
            } else {
                flash('error', 'Restore failed.');
            }
        } catch (Exception $e) {
            flash('error', 'Restore failed: ' . $e->getMessage());
        }

        redirect('/admin/backup');
    }
}
