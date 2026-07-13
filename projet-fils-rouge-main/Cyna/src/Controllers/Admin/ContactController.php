<?php

declare(strict_types=1);

namespace Cyna\Controllers\Admin;

use Cyna\Core\Controller;
use Cyna\Core\Exceptions\HttpException;
use Cyna\Core\Paginator;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Core\Session;
use Cyna\Repositories\ContactRepository;

/**
 * Boîte de réception des messages du formulaire de contact.
 */
final class ContactController extends Controller
{
    private const PER_PAGE = 20;
    private const STATUSES = ['new', 'read', 'archived'];

    public function index(Request $request): Response
    {
        $page = $request->int('page', 1);
        $result = ContactRepository::paginate(($page - 1) * self::PER_PAGE, self::PER_PAGE);

        return $this->view('admin/messages/index', [
            'title'     => 'Messages',
            'messages'  => $result['items'],
            'paginator' => new Paginator($result['total'], $page, self::PER_PAGE),
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $message = ContactRepository::find((int) $id);
        if ($message === null) {
            throw new HttpException(404, 'Message introuvable.');
        }

        // Marque automatiquement comme lu à l'ouverture.
        if ($message['status'] === 'new') {
            ContactRepository::updateStatus((int) $id, 'read');
            $message['status'] = 'read';
        }

        return $this->view('admin/messages/show', ['title' => 'Message', 'message' => $message]);
    }

    public function updateStatus(Request $request, string $id): Response
    {
        $status = $request->string('status');
        if (in_array($status, self::STATUSES, true)) {
            ContactRepository::updateStatus((int) $id, $status);
            Session::flash('success', 'Statut mis à jour.');
        }

        return $this->redirect('/admin/messages');
    }
}
