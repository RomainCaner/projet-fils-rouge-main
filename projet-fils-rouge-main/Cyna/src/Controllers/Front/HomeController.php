<?php

declare(strict_types=1);

namespace Cyna\Controllers\Front;

use Cyna\Core\Controller;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Repositories\CategoryRepository;
use Cyna\Repositories\HomeRepository;
use Cyna\Repositories\ProductRepository;

/**
 * Page d'accueil : carrousel, texte d'introduction, grille de catégories et
 * sélection des « Top produits du moment » — contenu piloté par le back-office.
 */
final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('front/home', [
            'title'          => t('home.title'),
            'slides'         => HomeRepository::activeSlides(),
            'introText'      => HomeRepository::setting('home_intro_text'),
            'categories'     => CategoryRepository::allWithCounts(),
            'featuredTitle'  => HomeRepository::setting('home_featured_title', t('home.featured')),
            'featured'       => ProductRepository::featured(8),
        ]);
    }
}
