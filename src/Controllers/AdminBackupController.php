<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Responses\FileResponse;
use App\Core\Responses\JsonResponse;
use App\Core\Responses\StreamResponse;
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

    public function streamDownload(Request $request): Response {
        return new StreamResponse(function() {
            try {
                $backup = $this->backupService->export(function($progress, $message) {
                    echo "data: " . json_encode(['progress' => $progress, 'message' => $message]) . "\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                });

                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                $_SESSION['last_backup'] = [
                    'path' => $backup['path'],
                    'filename' => $backup['filename'],
                    'mime' => $backup['mime'],
                    'temp' => $backup['temp']
                ];

                $this->logger->info("Admin generated database backup: {filename}", ['filename' => $backup['filename']]);

                echo "data: " . json_encode(['progress' => 100, 'message' => 'Backup complete!', 'done' => true]) . "\n\n";
            } catch (Exception $e) {
                $this->logger->error("Admin database backup failed: {error}", ['error' => $e->getMessage()]);
                echo "data: " . json_encode(['error' => 'Backup failed: ' . $e->getMessage()]) . "\n\n";
            }
        });
    }

    public function downloadFile(Request $request): Response {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $lastBackup = $_SESSION['last_backup'] ?? null;
        if (!$lastBackup || !file_exists($lastBackup['path'])) {
            flash('error', 'Backup file expired or not found.');
            return new RedirectResponse('/admin/backup');
        }

        unset($_SESSION['last_backup']);

        return new FileResponse(
            $lastBackup['path'],
            $lastBackup['filename'],
            $lastBackup['mime'],
            (bool)$lastBackup['temp']
        );
    }

    public function uploadTemp(Request $request): Response {
        $files = $_FILES;
        $file = $files['backup_file'] ?? null;
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return new JsonResponse(['success' => false, 'error' => 'No file uploaded.'], 400);
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new JsonResponse(['success' => false, 'error' => 'Upload failed with error code: ' . $file['error']], 400);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'json') {
            return new JsonResponse(['success' => false, 'error' => 'Only .json backup files are allowed.'], 400);
        }

        $tempDir = sys_get_temp_dir();
        $tempPath = tempnam($tempDir, 'restore_bak_');
        if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
            return new JsonResponse(['success' => false, 'error' => 'Failed to save uploaded file.'], 500);
        }

        return new JsonResponse([
            'success' => true,
            'temp_file' => $tempPath,
            'filename' => $file['name']
        ]);
    }

    public function streamRestore(Request $request): Response {
        $tempFile = $request->get('temp_file');
        $filename = $request->get('filename', 'backup.json');

        if (!$tempFile || !file_exists($tempFile)) {
            return new StreamResponse(function() {
                echo "data: " . json_encode(['error' => 'Temporary file not found or expired.']) . "\n\n";
            });
        }

        return new StreamResponse(function() use ($tempFile, $filename) {
            try {
                $fileArray = [
                    'tmp_name' => $tempFile,
                    'name' => $filename,
                    'error' => UPLOAD_ERR_OK
                ];

                $this->backupService->import($fileArray, function($progress, $message) {
                    echo "data: " . json_encode(['progress' => $progress, 'message' => $message]) . "\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                });

                @unlink($tempFile);

                $this->logger->info("Admin restored database from backup file: {filename}", ['filename' => $filename]);
                flash('msg', 'Database restored successfully.');

                echo "data: " . json_encode(['progress' => 100, 'message' => 'Restore complete!', 'done' => true]) . "\n\n";
            } catch (Exception $e) {
                @unlink($tempFile);
                $this->logger->error("Admin database restore failed: {error}", ['error' => $e->getMessage()]);
                echo "data: " . json_encode(['error' => 'Restore failed: ' . $e->getMessage()]) . "\n\n";
            }
        });
    }
}
