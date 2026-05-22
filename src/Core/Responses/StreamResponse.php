<?php
namespace App\Core\Responses;

use App\Core\Response;

class StreamResponse extends Response {
    /**
     * @var callable
     */
    private $callback;

    public function __construct(callable $callback, int $statusCode = 200, array $headers = []) {
        parent::__construct('', $statusCode, $headers);
        $this->callback = $callback;
        $this->setHeader('Content-Type', 'text/event-stream');
        $this->setHeader('Cache-Control', 'no-cache');
        $this->setHeader('Connection', 'keep-alive');
        $this->setHeader('X-Accel-Buffering', 'no');
    }

    public function send(): void {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Disable output buffering
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        ob_implicit_flush(true);

        // Execute the streaming callback
        ($this->callback)();
    }
}
