<?php

declare(strict_types=1);

namespace Cyna\Core;

/**
 * Réponse HTTP renvoyée au client.
 *
 * Centralise l'envoi du code de statut, des en-têtes (dont les en-têtes de
 * sécurité) et du corps de la réponse.
 */
final class Response
{
    /** @param array<string,string> $headers */
    public function __construct(
        private string $content = '',
        private int $status = 200,
        private array $headers = [],
    ) {
    }

    public static function html(string $content, int $status = 200): self
    {
        return new self($content, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self(
            (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    public static function download(string $content, string $filename, string $mime): self
    {
        return new self($content, 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length'      => (string) strlen($content),
        ]);
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->securityHeaders() + $this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }

        echo $this->content;
    }

    /**
     * En-têtes de sécurité appliqués à toutes les réponses
     * (protection clickjacking, sniffing MIME, politique de contenu).
     *
     * @return array<string,string>
     */
    private function securityHeaders(): array
    {
        return [
            'X-Frame-Options'        => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy'        => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; img-src 'self' data:; "
                . "script-src 'self' https://js.stripe.com; style-src 'self' https://fonts.googleapis.com; "
                . "font-src 'self' https://fonts.gstatic.com; frame-src https://js.stripe.com https://hooks.stripe.com; "
                . "connect-src 'self' https://api.stripe.com https://r.stripe.com",
        ];
    }
}
