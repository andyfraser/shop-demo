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
        $post = $_POST;
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (str_contains(strtolower($contentType), 'application/json')) {
            $rawBody = file_get_contents('php://input');
            $data = json_decode($rawBody, true);
            if (is_array($data)) {
                $post = array_merge($post, $data);
            }
        }
        return new self($_GET, $post, $_SERVER, $_FILES, $_COOKIE, $_SESSION ?? []);
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

    public function getBaseUrl(): string {
        return defined('BASE_URL') ? BASE_URL : '';
    }

    public function getOrigin(): string {
        $protocol = isset($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $this->server['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }

    public function getFullBaseUrl(): string {
        $baseUrl = $this->getBaseUrl();
        if (str_starts_with($baseUrl, 'http')) {
            return rtrim($baseUrl, '/');
        }
        return $this->getOrigin() . rtrim($baseUrl, '/');
    }
}
