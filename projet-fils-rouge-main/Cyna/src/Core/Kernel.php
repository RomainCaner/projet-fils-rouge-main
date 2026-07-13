<?php

declare(strict_types=1);

namespace Cyna\Core;

use Cyna\Core\Exceptions\HttpException;
use Throwable;

/**
 * Cœur de l'application : reçoit une requête, exécute la chaîne de middlewares
 * puis l'action du contrôleur, et transforme toute exception en réponse HTTP.
 */
final class Kernel
{
    public function __construct(private readonly Router $router)
    {
    }

    public function handle(Request $request): Response
    {
        try {
            $route = $this->router->match($request);

            // Exécution des middlewares : le premier qui renvoie une réponse
            // court-circuite l'action (redirection login, erreur CSRF, ...).
            foreach ($route['middleware'] as $middlewareClass) {
                $response = (new $middlewareClass())->handle($request);
                if ($response instanceof Response) {
                    return $response;
                }
            }

            [$controllerClass, $method] = $route['action'];
            $controller = new $controllerClass();

            return $controller->$method($request, ...array_values($route['params']));
        } catch (HttpException $e) {
            return $this->renderError($e->statusCode, $e->getMessage());
        } catch (Throwable $e) {
            return $this->renderException($e);
        }
    }

    private function renderError(int $status, string $message): Response
    {
        $html = (new View())->render('errors/error', [
            'status'  => $status,
            'message' => $message ?: 'Une erreur est survenue.',
        ]);

        return Response::html($html, $status);
    }

    private function renderException(Throwable $e): Response
    {
        // En production on masque les détails ; en local on aide au débogage.
        if (Config::isProduction()) {
            error_log((string) $e);

            return $this->renderError(500, 'Erreur interne du serveur.');
        }

        $message = sprintf(
            "%s\n%s:%d\n\n%s",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString(),
        );

        return Response::html('<pre style="padding:2rem;white-space:pre-wrap">' . e($message) . '</pre>', 500);
    }
}
