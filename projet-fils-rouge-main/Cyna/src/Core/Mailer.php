<?php

declare(strict_types=1);

namespace Cyna\Core;

use RuntimeException;

/**
 * Client SMTP minimaliste (sans dépendance) pour l'envoi d'e-mails HTML.
 *
 * Conçu pour dialoguer avec un serveur de test (Mailpit/MailHog) en local et
 * un relais SMTP en production. Gère les chiffrements `tls` (STARTTLS) et `ssl`.
 */
final class Mailer
{
    /**
     * Envoie un e-mail au format HTML, avec d'éventuelles pièces jointes.
     *
     * @param list<array{filename:string,content:string,mime?:string}> $attachments
     * @throws RuntimeException en cas d'échec du dialogue SMTP
     */
    public static function send(string $toEmail, string $subject, string $htmlBody, array $attachments = []): void
    {
        $host = (string) Config::get('mail.host');
        $port = (int) Config::get('mail.port');
        $encryption = (string) Config::get('mail.encryption');
        $fromEmail = (string) Config::get('mail.from_email');
        $fromName = (string) Config::get('mail.from_name');

        $transport = $encryption === 'ssl' ? 'ssl://' : '';
        $socket = @stream_socket_client($transport . $host . ':' . $port, $errno, $errstr, 15);
        if ($socket === false) {
            throw new RuntimeException("Connexion SMTP impossible ({$errstr}).");
        }

        self::expect($socket, 220);
        self::command($socket, 'EHLO ' . self::clientName(), 250);

        if ($encryption === 'tls') {
            self::command($socket, 'STARTTLS', 220);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            self::command($socket, 'EHLO ' . self::clientName(), 250);
        }

        $username = (string) Config::get('mail.username');
        $password = (string) Config::get('mail.password');
        if ($username !== '') {
            self::command($socket, 'AUTH LOGIN', 334);
            self::command($socket, base64_encode($username), 334);
            self::command($socket, base64_encode($password), 235);
        }

        self::command($socket, 'MAIL FROM:<' . $fromEmail . '>', 250);
        self::command($socket, 'RCPT TO:<' . $toEmail . '>', 250);
        self::command($socket, 'DATA', 354);

        $message = self::buildMessage($fromName, $fromEmail, $toEmail, $subject, $htmlBody, $attachments);
        self::command($socket, $message . "\r\n.", 250);
        self::command($socket, 'QUIT', 221);

        fclose($socket);
    }

    /**
     * Construit le corps MIME complet (en-têtes + contenu).
     *
     * Sans pièce jointe : message HTML simple (base64). Avec pièces jointes :
     * enveloppe multipart/mixed (partie HTML + une partie par fichier).
     *
     * @param list<array{filename:string,content:string,mime?:string}> $attachments
     */
    private static function buildMessage(
        string $fromName,
        string $fromEmail,
        string $toEmail,
        string $subject,
        string $htmlBody,
        array $attachments,
    ): string {
        $common = [
            'From: ' . self::encodeName($fromName) . ' <' . $fromEmail . '>',
            'To: <' . $toEmail . '>',
            'Subject: ' . self::encodeName($subject),
            'MIME-Version: 1.0',
        ];

        if ($attachments === []) {
            $headers = implode("\r\n", [
                ...$common,
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
            ]);

            return $headers . "\r\n\r\n" . chunk_split(base64_encode($htmlBody));
        }

        $boundary = 'cyna_' . bin2hex(random_bytes(12));
        $headers = implode("\r\n", [
            ...$common,
            'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
        ]);

        $body = '--' . $boundary . "\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($htmlBody)) . "\r\n";

        foreach ($attachments as $attachment) {
            $name = str_replace('"', '', $attachment['filename']);
            $mime = $attachment['mime'] ?? 'application/octet-stream';
            $body .= '--' . $boundary . "\r\n"
                . 'Content-Type: ' . $mime . '; name="' . $name . "\"\r\n"
                . "Content-Transfer-Encoding: base64\r\n"
                . 'Content-Disposition: attachment; filename="' . $name . "\"\r\n\r\n"
                . chunk_split(base64_encode($attachment['content'])) . "\r\n";
        }

        $body .= '--' . $boundary . '--';

        return $headers . "\r\n\r\n" . $body;
    }

    /** @param resource $socket */
    private static function command($socket, string $command, int $expected): void
    {
        fwrite($socket, $command . "\r\n");
        self::expect($socket, $expected);
    }

    /** @param resource $socket */
    private static function expect($socket, int $code): void
    {
        $response = (string) fgets($socket, 512);
        // Consomme les lignes multiples d'une même réponse (ex: "250-...").
        while (strlen($response) >= 4 && $response[3] === '-') {
            $response = (string) fgets($socket, 512);
        }

        if ((int) substr($response, 0, 3) !== $code) {
            throw new RuntimeException('Réponse SMTP inattendue : ' . trim($response));
        }
    }

    private static function clientName(): string
    {
        return parse_url((string) Config::get('app.url'), PHP_URL_HOST) ?: 'localhost';
    }

    private static function encodeName(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
