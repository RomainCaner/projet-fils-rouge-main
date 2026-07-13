<?php

declare(strict_types=1);

namespace Cyna\Controllers\Front;

use Cyna\Core\Controller;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Core\Translator;

/**
 * Changement de langue : mémorise la locale puis renvoie l'utilisateur sur la
 * page qu'il consultait (sans la perdre), conformément au cahier des charges.
 */
final class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): Response
    {
        Translator::setLocale($locale);

        $referer = $_SERVER['HTTP_REFERER'] ?? url('/');

        return Response::redirect($referer);
    }
}
