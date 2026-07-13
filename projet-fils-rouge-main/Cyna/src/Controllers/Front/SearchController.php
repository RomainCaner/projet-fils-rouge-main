<?php

declare(strict_types=1);

namespace Cyna\Controllers\Front;

use Cyna\Core\Controller;
use Cyna\Core\Paginator;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Repositories\CategoryRepository;
use Cyna\Services\SearchService;

/**
 * Recherche avancée avec facettes (texte, catégories, prix, disponibilité) et
 * options de tri (prix, nouveauté, disponibilité).
 */
final class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->extractFilters($request);
        $perPage = 9;
        $page = $request->int('page', 1);

        $result = SearchService::search($filters, ($page - 1) * $perPage, $perPage);
        $paginator = new Paginator($result['total'], $page, $perPage);

        return $this->view('front/search', [
            'title'      => t('search.title'),
            'filters'    => $filters,
            'categories' => CategoryRepository::all(),
            'products'   => $result['items'],
            'paginator'  => $paginator,
        ]);
    }

    /**
     * @return array{q:string,categories:list<int>,price_min:?int,price_max:?int,available_only:bool,sort:string,dir:string}
     */
    private function extractFilters(Request $request): array
    {
        $categories = array_map('intval', (array) $request->input('categories', []));
        $priceMin = $request->string('price_min');
        $priceMax = $request->string('price_max');

        return [
            'q'              => $request->string('q'),
            'categories'     => array_values(array_filter($categories)),
            // Les prix de l'interface sont en euros, convertis en centimes.
            'price_min'      => $priceMin !== '' ? (int) round((float) $priceMin * 100) : null,
            'price_max'      => $priceMax !== '' ? (int) round((float) $priceMax * 100) : null,
            'available_only' => $request->boolean('available_only'),
            'sort'           => $request->string('sort'),
            'dir'            => $request->string('dir', 'asc'),
        ];
    }
}
