<?php

declare(strict_types=1);

namespace Cyna\Controllers\Admin;

use Cyna\Core\Controller;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Repositories\ContactRepository;
use Cyna\Repositories\OrderRepository;

/**
 * Tableau de bord du back-office : indicateurs de vente et graphiques.
 *
 * Les graphiques (histogramme, camembert) sont dessinés côté client à partir
 * des données JSON exposées par l'action `data()`, ce qui permet de changer la
 * période (7 derniers jours / 5 dernières semaines) sans recharger la page.
 */
final class DashboardController extends Controller
{
    private const PERIODS = [7 => '7 derniers jours', 35 => '5 dernières semaines'];

    public function index(Request $request): Response
    {
        $days = $this->period($request);

        return $this->view('admin/dashboard', [
            'title'    => 'Tableau de bord',
            'days'     => $days,
            'periods'  => self::PERIODS,
            'summary'  => OrderRepository::summary($days),
            'newMessages' => ContactRepository::countNew(),
        ]);
    }

    /** Données des graphiques au format JSON (consommées par charts.js). */
    public function data(Request $request): Response
    {
        $days = $this->period($request);

        return $this->json([
            'salesByDay'      => OrderRepository::salesByDay($days),
            'salesByCategory' => OrderRepository::salesByCategory($days),
        ]);
    }

    private function period(Request $request): int
    {
        $days = $request->int('period', 7);

        return array_key_exists($days, self::PERIODS) ? $days : 7;
    }
}
