<?php

declare(strict_types=1);

namespace Cyna\Tests\Unit;

use Cyna\Core\Paginator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie la pagination des listes (exigence « pagination obligatoire sur
 * toutes les listes », note de cadrage §4.4).
 */
#[CoversClass(Paginator::class)]
final class PaginatorTest extends TestCase
{
    public function testComputesLastPageAndOffset(): void
    {
        $paginator = new Paginator(total: 25, page: 2, perPage: 12);

        self::assertSame(3, $paginator->lastPage, '25 éléments / 12 par page => 3 pages');
        self::assertSame(2, $paginator->page);
        self::assertSame(12, $paginator->offset, 'offset de la page 2 = 12');
    }

    public function testClampsPageAboveLastPage(): void
    {
        $paginator = new Paginator(total: 25, page: 99, perPage: 12);

        self::assertSame(3, $paginator->page, 'une page trop grande est ramenée à la dernière');
        self::assertSame(24, $paginator->offset);
        self::assertFalse($paginator->hasNext());
        self::assertTrue($paginator->hasPrevious());
    }

    public function testClampsPageBelowOne(): void
    {
        $paginator = new Paginator(total: 25, page: 0, perPage: 12);

        self::assertSame(1, $paginator->page);
        self::assertSame(0, $paginator->offset);
        self::assertTrue($paginator->hasNext());
        self::assertFalse($paginator->hasPrevious());
    }

    public function testEmptyResultStillHasOnePage(): void
    {
        $paginator = new Paginator(total: 0, page: 1, perPage: 12);

        self::assertSame(1, $paginator->lastPage);
        self::assertSame(0, $paginator->total);
        self::assertFalse($paginator->hasNext());
        self::assertFalse($paginator->hasPrevious());
    }

    public function testPerPageIsAtLeastOne(): void
    {
        $paginator = new Paginator(total: 5, page: 1, perPage: 0);

        self::assertSame(1, $paginator->perPage);
        self::assertSame(5, $paginator->lastPage);
    }

    public function testUrlPreservesQueryParameters(): void
    {
        $paginator = new Paginator(total: 25, page: 1, perPage: 12);

        $url = $paginator->url('/recherche', 3, ['q' => 'edr', 'sort' => 'price']);

        self::assertStringContainsString('page=3', $url);
        self::assertStringContainsString('q=edr', $url);
        self::assertStringContainsString('sort=price', $url);
        self::assertStringStartsWith('/recherche?', $url);
    }
}
