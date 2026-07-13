<?php

declare(strict_types=1);

namespace Cyna\Core;

/**
 * Contrôleur de base : fabriques de réponses partagées par tous les contrôleurs.
 */
abstract class Controller
{
    /**
     * Rend un gabarit en réponse HTML.
     *
     * @param array<string,mixed> $data
     */
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        $html = (new View())->render($template, $data);

        return Response::html($html, $status);
    }

    protected function redirect(string $url): Response
    {
        return Response::redirect(url($url));
    }

    /**
     * Redirige vers la page précédente en conservant erreurs et saisies.
     *
     * @param array<string,list<string>> $errors
     * @param array<string,mixed>         $input
     */
    protected function back(array $errors = [], array $input = []): Response
    {
        if ($errors !== []) {
            Session::set('_errors', $errors);
        }
        if ($input !== []) {
            Session::flashInput($input);
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? url('/');

        return Response::redirect($referer);
    }

    /** @param array<string,mixed>|list<mixed> $data */
    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }
}
