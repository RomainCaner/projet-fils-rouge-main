<?php

declare(strict_types=1);

namespace Cyna\Core;

/**
 * Représentation immuable de la requête HTTP entrante.
 */
final class Request
{
    /**
     * @param array<string,mixed> $query   Paramètres GET
     * @param array<string,mixed> $body    Paramètres POST
     * @param array<string,mixed> $server  Variables serveur ($_SERVER)
     * @param array<string,mixed> $files   Fichiers uploadés ($_FILES)
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $query,
        private readonly array $body,
        private readonly array $server,
        private readonly array $files,
    ) {
    }

    public static function capture(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = '/' . trim(parse_url($uri, PHP_URL_PATH) ?: '/', '/');

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $path === '//' ? '/' : $path,
            $_GET,
            $_POST,
            $_SERVER,
            $_FILES,
        );
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function has(string $key): bool
    {
        return $this->input($key) !== null;
    }

    public function boolean(string $key): bool
    {
        return in_array($this->input($key), ['1', 'true', 'on', 'yes', true], true);
    }

    /** @return array<string,mixed> */
    public function all(): array
    {
        return $this->body + $this->query;
    }

    /** @return array<string,mixed>|null */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $file;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function header(string $name, string $default = ''): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return (string) ($this->server[$key] ?? $default);
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function isSecure(): bool
    {
        return ($this->server['HTTPS'] ?? '') === 'on'
            || ($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }
}
