<?php

declare(strict_types=1);

namespace Cyna\Services;

use Cyna\Repositories\ProductRepository;

/**
 * Recherche avancée de services SaaS.
 *
 * Applique les facettes (catégories, prix, disponibilité) côté base, puis le
 * classement par pertinence du texte selon la règle métier du cahier des
 * charges, par ordre de priorité décroissante :
 *   1. correspondance exacte ;
 *   2. un seul caractère de différence (distance de Levenshtein = 1) ;
 *   3. commence par le texte recherché ;
 *   4. contient le texte recherché.
 *
 * Le tri (prix, nouveauté, disponibilité) reste configurable par l'utilisateur.
 */
final class SearchService
{
    private const RELEVANCE_EXACT = 0;
    private const RELEVANCE_ONE_CHAR = 1;
    private const RELEVANCE_STARTS = 2;
    private const RELEVANCE_CONTAINS = 3;
    private const RELEVANCE_NONE = 99;

    /**
     * @param array{q?:string,categories?:list<int>,price_min?:int,price_max?:int,available_only?:bool,sort?:string,dir?:string} $filters
     * @return array{items:list<array<string,mixed>>,total:int}
     */
    public static function search(array $filters, int $offset, int $perPage): array
    {
        $candidates = ProductRepository::searchCandidates([
            'categories'     => $filters['categories'] ?? [],
            'price_min'      => $filters['price_min'] ?? null,
            'price_max'      => $filters['price_max'] ?? null,
            'available_only' => $filters['available_only'] ?? false,
        ]);

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $candidates = self::rankByRelevance($candidates, $query);
        }

        $candidates = self::sort($candidates, (string) ($filters['sort'] ?? ''), (string) ($filters['dir'] ?? 'asc'), $query !== '');

        return [
            'items' => array_slice($candidates, $offset, $perPage),
            'total' => count($candidates),
        ];
    }

    /**
     * Filtre puis annote chaque candidat d'un score de pertinence textuelle.
     *
     * @param list<array<string,mixed>> $candidates
     * @return list<array<string,mixed>>
     */
    private static function rankByRelevance(array $candidates, string $query): array
    {
        $needle = mb_strtolower($query);
        $matched = [];

        foreach ($candidates as $product) {
            $name = mb_strtolower((string) $product['name']);
            $description = mb_strtolower((string) $product['description'] . ' ' . $product['short_description']);

            $score = self::RELEVANCE_NONE;
            if ($name === $needle) {
                $score = self::RELEVANCE_EXACT;
            } elseif (levenshtein($name, $needle) === 1) {
                $score = self::RELEVANCE_ONE_CHAR;
            } elseif (str_starts_with($name, $needle)) {
                $score = self::RELEVANCE_STARTS;
            } elseif (str_contains($name, $needle) || str_contains($description, $needle)) {
                $score = self::RELEVANCE_CONTAINS;
            }

            if ($score !== self::RELEVANCE_NONE) {
                $product['_relevance'] = $score;
                $matched[] = $product;
            }
        }

        return $matched;
    }

    /**
     * @param list<array<string,mixed>> $items
     * @return list<array<string,mixed>>
     */
    private static function sort(array $items, string $sort, string $dir, bool $hasQuery): array
    {
        $descending = strtolower($dir) === 'desc';

        usort($items, static function (array $a, array $b) use ($sort, $descending, $hasQuery): int {
            $comparison = match ($sort) {
                'price'        => (int) $a['price_monthly_cents'] <=> (int) $b['price_monthly_cents'],
                'newness'      => strcmp((string) $a['created_at'], (string) $b['created_at']),
                'availability' => ($b['availability'] === 'available' ? 1 : 0) <=> ($a['availability'] === 'available' ? 1 : 0),
                default        => 0,
            };

            if ($comparison !== 0) {
                return $descending ? -$comparison : $comparison;
            }

            // Tri secondaire : pertinence (si recherche texte) puis priorité métier.
            if ($hasQuery) {
                $relevance = ($a['_relevance'] ?? 0) <=> ($b['_relevance'] ?? 0);
                if ($relevance !== 0) {
                    return $relevance;
                }
            }

            $available = ($b['availability'] === 'available' ? 1 : 0) <=> ($a['availability'] === 'available' ? 1 : 0);

            return $available !== 0 ? $available : (int) $b['priority'] <=> (int) $a['priority'];
        });

        return $items;
    }
}
