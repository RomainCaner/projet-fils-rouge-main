<?php

declare(strict_types=1);

namespace Cyna\Controllers\Admin;

use Cyna\Core\Controller;
use Cyna\Core\Paginator;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Repositories\UserRepository;

/**
 * Consultation des utilisateurs au back-office (liste paginée, recherche).
 */
final class UserController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): Response
    {
        $page = $request->int('page', 1);
        $search = $request->string('q');
        $result = UserRepository::paginate(($page - 1) * self::PER_PAGE, self::PER_PAGE, $search);

        return $this->view('admin/users/index', [
            'title'     => 'Utilisateurs',
            'users'     => $result['items'],
            'paginator' => new Paginator($result['total'], $page, self::PER_PAGE),
            'q'         => $search,
        ]);
    }
}
