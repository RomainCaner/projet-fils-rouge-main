<?php

declare(strict_types=1);

namespace Cyna\Controllers\Front;

use Cyna\Core\Controller;
use Cyna\Core\Exceptions\HttpException;
use Cyna\Core\Paginator;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Repositories\CategoryRepository;
use Cyna\Repositories\HomeRepository;
use Cyna\Repositories\ProductRepository;

/**
 * Page d'accès au catalogue d'une catégorie (image, description, liste paginée
 * des services triés selon la règle disponibilité + priorité).
 */
final class CatalogController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        $category = CategoryRepository::findBySlug($slug);
        if ($category === null) {
            throw new HttpException(404, t('catalog.not_found'));
        }

        $perPage = (int) HomeRepository::setting('pagination_per_page', '9');
        $page = $request->int('page', 1);
        $result = ProductRepository::byCategory((int) $category['id'], ($page - 1) * $perPage, $perPage);
        $paginator = new Paginator($result['total'], $page, $perPage);

        return $this->view('front/catalog', [
            'title'     => (string) $category['name'],
            'category'  => $category,
            'products'  => $result['items'],
            'paginator' => $paginator,
        ]);
    }
}
