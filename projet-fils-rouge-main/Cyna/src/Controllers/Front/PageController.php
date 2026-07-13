<?php

declare(strict_types=1);

namespace Cyna\Controllers\Front;

use Cyna\Core\Controller;
use Cyna\Core\Request;
use Cyna\Core\Response;

/**
 * Pages statiques légales et institutionnelles.
 */
final class PageController extends Controller
{
    public function terms(Request $request): Response
    {
        return $this->view('front/pages/terms', ['title' => t('pages.terms')]);
    }

    public function legal(Request $request): Response
    {
        return $this->view('front/pages/legal', ['title' => t('pages.legal')]);
    }

    public function privacy(Request $request): Response
    {
        return $this->view('front/pages/privacy', ['title' => t('pages.privacy')]);
    }

    public function about(Request $request): Response
    {
        return $this->view('front/pages/about', ['title' => t('pages.about')]);
    }
}
