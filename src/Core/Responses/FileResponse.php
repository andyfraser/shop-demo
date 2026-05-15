<?php
namespace App\Core\Responses;

use App\Core\Response;

class FileResponse extends Response {
    public function __construct(private string $filePath, string $filename, string $mime, private bool $deleteAfterSend = false) {
        parent::__construct('');
        $this->setHeader('Content-Type', $mime);
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->setHeader('Content-Length', (string)filesize($filePath));
    }

    public function send(): void {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        readfile($this->filePath);
        if ($this->deleteAfterSend) {
            @unlink($this->filePath);
        }
    }
}
