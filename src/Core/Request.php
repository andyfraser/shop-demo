<?php
namespace App\Core;

class Request {
    public function __construct(
        private array $query,
        private array $post,
        private array $server,
        private array $files,
        private array $cookies,
        private array $session
    ) {}

    public static function createFromGlobals(): self {
        return new self($_GET, $_POST, $_SERVER, $_FILES, $_COOKIE, $_SESSION ?? []);
    }

    public function getQuery(?string $key = null, mixed $default = null): mixed {
        if ($key === null) return $this->query;
        return $this->query[$key] ?? $default;
    }

    public function getPost(?string $key = null, mixed $default = null): mixed {
        if ($key === null) return $this->post;
        return $this->post[$key] ?? $default;
    }

    public function getServer(?string $key = null, mixed $default = null): mixed {
        if ($key === null) return $this->server;
        return $this->server[$key] ?? $default;
    }

    public function getCookie(?string $key = null, mixed $default = null): mixed {
        if ($key === null) return $this->cookies;
        return $this->cookies[$key] ?? $default;
    }

    public function getSession(?string $key = null, mixed $default = null): mixed {
        if ($key === null) return $this->session;
        return $this->session[$key] ?? $default;
    }

    public function getMethod(): string {
        return strtoupper($this->getServer('REQUEST_METHOD', 'GET'));
    }

    public function getUri(): string {
        return $this->getServer('REQUEST_URI', '/');
    }

    public function getPath(): string {
        return parse_url($this->getUri(), PHP_URL_PATH) ?: '/';
    }

    public function isAjax(): bool {
        return (isset($this->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($this->query['ajax']);
    }

    public function isPost(): bool {
        return $this->getMethod() === 'POST';
    }
}
