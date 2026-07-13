<?php

declare(strict_types=1);

namespace Cyna\Controllers\Admin;

use Cyna\Core\Controller;
use Cyna\Core\Exceptions\HttpException;
use Cyna\Core\Paginator;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Core\Session;
use Cyna\Repositories\OrderRepository;

/**
 * Gestion des commandes au back-office : liste filtrable, détail et mise à jour
 * du statut.
 */
final class OrderController extends Controller
{
    private const PER_PAGE = 20;
    private const STATUSES = ['pending', 'paid', 'active', 'renewed', 'cancelled', 'failed'];

    public function index(Request $request): Response
    {
        $page = $request->int('page', 1);
        $status = $request->string('status');
        $status = in_array($status, self::STATUSES, true) ? $status : '';

        $result = OrderRepository::paginate(($page - 1) * self::PER_PAGE, self::PER_PAGE, $status);

        return $this->view('admin/orders/index', [
            'title'     => 'Commandes',
            'orders'    => $result['items'],
            'paginator' => new Paginator($result['total'], $page, self::PER_PAGE),
            'status'    => $status,
            'statuses'  => self::STATUSES,
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $order = OrderRepository::find((int) $id);
        if ($order === null) {
            throw new HttpException(404, 'Commande introuvable.');
        }

        return $this->view('admin/orders/show', [
            'title'    => 'Commande ' . $order['invoice_number'],
            'order'    => $order,
            'items'    => OrderRepository::items((int) $order['id']),
            'statuses' => self::STATUSES,
        ]);
    }

    /**
     * Export CSV des commandes (rapport d'administration exportable).
     * Respecte le filtre de statut éventuellement actif sur la liste.
     */
    public function export(Request $request): Response
    {
        $status = $request->string('status');
        $status = in_array($status, self::STATUSES, true) ? $status : '';

        $orders = OrderRepository::allForExport($status);

        $columns = ['Facture', 'Date', 'E-mail', 'Statut', 'Sous-total (€)', 'Code promo', 'Remise (€)', 'Total (€)'];
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $columns, ';');

        foreach ($orders as $order) {
            $discount = (int) ($order['discount_cents'] ?? 0);
            $total = (int) $order['total_cents'];
            fputcsv($handle, [
                $order['invoice_number'],
                substr((string) $order['created_at'], 0, 16),
                $order['email'],
                $order['status'],
                number_format(($total + $discount) / 100, 2, ',', ''),
                $order['discount_code'] ?? '',
                number_format($discount / 100, 2, ',', ''),
                number_format($total / 100, 2, ',', ''),
            ], ';');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        // BOM UTF-8 pour un affichage correct des accents dans Excel.
        $filename = 'commandes-cyna-' . date('Y-m-d') . '.csv';

        return Response::download("\xEF\xBB\xBF" . $csv, $filename, 'text/csv; charset=UTF-8');
    }

    public function updateStatus(Request $request, string $id): Response
    {
        $status = $request->string('status');
        if (!in_array($status, self::STATUSES, true)) {
            return $this->back(['status' => ['Statut invalide.']]);
        }

        OrderRepository::updateStatus((int) $id, $status);
        Session::flash('success', 'Statut mis à jour.');

        return $this->redirect('/admin/commandes/' . $id);
    }
}
