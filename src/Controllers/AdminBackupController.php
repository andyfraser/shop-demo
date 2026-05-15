<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Responses\FileResponse;
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

    public function index(Request $request): Response {
        return new HtmlResponse($this->renderer->adminRender('backup', [
            'page_title' => 'Database Backup & Restore',
            'active'     => 'backup',
            'flash_msg'  => flash('msg'),
            'error_msg'  => flash('error'),
        ]));
    }

    public function download(Request $request): Response {
        try {
            $backup = $this->backupService->export();
            $this->logger->info("Admin downloaded database backup");
            return new FileResponse($backup['path'], $backup['filename'], $backup['mime'], (bool)$backup['temp']);
        } catch (Exception $e) {
            flash('error', 'Backup failed: ' . $e->getMessage());
            return new RedirectResponse('/admin/backup');
        }
    }

    public function restore(Request $request): Response {
        $files = $_FILES; // Still using $_FILES for now as Request doesn't handle them fully yet
        $file = $files['backup_file'] ?? null;
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            flash('error', 'Please select a file to restore.');
            return new RedirectResponse('/admin/backup');
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

        return new RedirectResponse('/admin/backup');
    }
}
