<?php

declare(strict_types=1);

namespace Cyna\Controllers\Admin;

use Cyna\Core\Controller;
use Cyna\Core\Paginator;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Repositories\SubscriptionRepository;

/**
 * Vue des abonnements dans le back-office : liste paginée et filtrable par
 * statut, avec l'e-mail du client, la périodicité, l'échéance et l'état du
 * renouvellement automatique.
 */
final class SubscriptionController extends Controller
{
    private const PER_PAGE = 20;
    private const STATUSES = ['active', 'cancelled', 'expired'];

    public function index(Request $request): Response
    {
        $page = $request->int('page', 1);
        $status = $request->string('status');
        $status = in_array($status, self::STATUSES, true) ? $status : '';

        $result = SubscriptionRepository::paginate(($page - 1) * self::PER_PAGE, self::PER_PAGE, $status);

        return $this->view('admin/subscriptions/index', [
            'title'         => 'Abonnements',
            'subscriptions' => $result['items'],
            'paginator'     => new Paginator($result['total'], $page, self::PER_PAGE),
            'status'        => $status,
            'statuses'      => self::STATUSES,
        ]);
    }
}
