<?php
namespace App\Controllers;

use App\Core\Renderer;
use App\Services\SecurityService;
use App\Services\BackupService;
use Exception;

class AdminBackupController {
    public function index() {
        Renderer::adminRender('backup', [
            'page_title' => 'Database Backup & Restore',
            'active'     => 'backup',
            'flash_msg'  => flash('msg'),
            'error_msg'  => flash('error'),
        ]);
    }

    public function download() {
        SecurityService::verifyCsrf();

        try {
            $backup = BackupService::export();
            
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
        SecurityService::verifyCsrf();

        $file = $_FILES['backup_file'] ?? null;
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            flash('error', 'Please select a file to restore.');
            redirect('/admin/backup');
        }

        try {
            if (BackupService::import($file)) {
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
