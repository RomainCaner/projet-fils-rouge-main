<?php

declare(strict_types=1);

namespace Cyna\Core;

use Cyna\Core\Exceptions\HttpException;

/**
 * Routeur HTTP minimaliste.
 *
 * Associe une méthode + un motif d'URL à une action de contrôleur, avec une
 * liste de middlewares exécutés avant l'action. Les segments dynamiques sont
 * déclarés sous la forme `{nom}` et injectés dans la méthode du contrôleur.
 */
final class Router
{
    /** @var list<array{method:string,regex:string,params:list<string>,action:array{0:class-string,1:string},middleware:list<class-string>}> */
    private array $routes = [];

    /** @var list<class-string> Middlewares appliqués au groupe courant. */
    private array $groupMiddleware = [];

    private string $groupPrefix = '';

    /**
     * Définit un groupe de routes partageant un préfixe d'URL et des middlewares.
     *
     * @param list<class-string> $middleware
     */
    public function group(string $prefix, array $middleware, callable $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix .= $prefix;
        $this->groupMiddleware = [...$this->groupMiddleware, ...$middleware];

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    /** @param array{0:class-string,1:string} $action */
    public function get(string $path, array $action, array $middleware = []): void
    {
        $this->add('GET', $path, $action, $middleware);
    }

    /** @param array{0:class-string,1:string} $action */
    public function post(string $path, array $action, array $middleware = []): void
    {
        $this->add('POST', $path, $action, $middleware);
    }

    /**
     * @param array{0:class-string,1:string} $action
     * @param list<class-string> $middleware
     */
    private function add(string $method, string $path, array $action, array $middleware): void
    {
        $path = $this->groupPrefix . $path;
        $params = [];

        $regex = preg_replace_callback('#\{(\w+)\}#', static function (array $m) use (&$params): string {
            $params[] = $m[1];

            return '([^/]+)';
        }, $path);

        $this->routes[] = [
            'method'     => $method,
            'regex'      => '#^' . $regex . '$#',
            'params'     => $params,
            'action'     => $action,
            'middleware' => [...$this->groupMiddleware, ...$middleware],
        ];
    }

    /**
     * Recherche la route correspondant à la requête.
     *
     * @return array{action:array{0:class-string,1:string},params:array<string,string>,middleware:list<class-string>}
     * @throws HttpException si aucune route ne correspond (404)
     */
    public function match(Request $request): array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }

            if (preg_match($route['regex'], $request->path, $matches) === 1) {
                array_shift($matches);
                $params = array_combine($route['params'], $matches) ?: [];

                return [
                    'action'     => $route['action'],
                    'params'     => $params,
                    'middleware' => $route['middleware'],
                ];
            }
        }

        throw new HttpException(404, 'Page introuvable.');
    }
}
