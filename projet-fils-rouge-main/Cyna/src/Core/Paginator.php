<?php

declare(strict_types=1);

namespace Cyna\Core;

/**
 * Pagination des listes (front-office et back-office).
 *
 * Conformément au cahier des charges, toutes les listes de services sont
 * paginées. Le nombre d'éléments par page est paramétrable.
 */
final class Paginator
{
    public readonly int $page;
    public readonly int $perPage;
    public readonly int $total;
    public readonly int $lastPage;
    public readonly int $offset;

    public function __construct(int $total, int $page, int $perPage = 12)
    {
        $this->perPage = max(1, $perPage);
        $this->total = max(0, $total);
        $this->lastPage = max(1, (int) ceil($this->total / $this->perPage));
        $this->page = min(max(1, $page), $this->lastPage);
        $this->offset = ($this->page - 1) * $this->perPage;
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->lastPage;
    }

    /**
     * Construit l'URL d'une page en conservant les paramètres de requête.
     *
     * @param array<string,mixed> $query
     */
    public function url(string $path, int $page, array $query = []): string
    {
        $query['page'] = $page;

        return $path . '?' . http_build_query($query);
    }
}
