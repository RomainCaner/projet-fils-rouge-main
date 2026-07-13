<?php

declare(strict_types=1);

namespace Cyna\Controllers\Front;

use Cyna\Core\Controller;
use Cyna\Core\Exceptions\HttpException;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Repositories\ProductRepository;

/**
 * Fiche d'un service SaaS : carrousel d'illustrations, description,
 * caractéristiques techniques, tarif, disponibilité et services similaires.
 */
final class ProductController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        $product = ProductRepository::findBySlug($slug);
        if ($product === null) {
            throw new HttpException(404, t('product.not_found'));
        }

        return $this->view('front/product', [
            'title'    => (string) $product['name'],
            'product'  => $product,
            'images'   => ProductRepository::images((int) $product['id']),
            'similar'  => ProductRepository::similar((int) $product['id'], (int) $product['category_id'], 6),
        ]);
    }
}
