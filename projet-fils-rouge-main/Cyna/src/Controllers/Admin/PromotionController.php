<?php

declare(strict_types=1);

namespace Cyna\Controllers\Admin;

use Cyna\Core\Controller;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Core\Session;
use Cyna\Core\Validator;
use Cyna\Repositories\PromotionRepository;

/**
 * Gestion des codes de réduction (promotions) : liste, création, activation
 * et suppression. Réservé aux administrateurs.
 */
final class PromotionController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin/promotions/index', [
            'title'      => 'Codes de réduction',
            'promotions' => PromotionRepository::all(),
        ]);
    }

    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'code'  => 'required|max:40',
            'type'  => 'required|in:percent,fixed',
            'value' => 'required|numeric',
        ], [
            'code'  => 'Code',
            'type'  => 'Type',
            'value' => 'Valeur',
        ]);

        if ($validator->fails()) {
            return $this->back($validator->errors(), $request->all());
        }

        // Un pourcentage est saisi en %, un montant fixe en euros → conversion en centimes.
        $value = $request->int('value');
        if ($request->string('type') === 'fixed') {
            $value = (int) round((float) $request->string('value') * 100);
        }

        PromotionRepository::create([
            'code'       => $request->string('code'),
            'title'      => $request->string('title'),
            'type'       => $request->string('type'),
            'value'      => $value,
            'active'     => 1,
            'expires_at' => $request->string('expires_at') !== '' ? $request->string('expires_at') : null,
            'max_uses'   => $request->string('max_uses'),
        ]);
        Session::flash('success', 'Code de réduction créé.');

        return $this->redirect('/admin/promotions');
    }

    public function toggle(Request $request, string $id): Response
    {
        PromotionRepository::toggle((int) $id);
        Session::flash('success', 'Statut du code mis à jour.');

        return $this->redirect('/admin/promotions');
    }

    public function delete(Request $request, string $id): Response
    {
        PromotionRepository::delete((int) $id);
        Session::flash('success', 'Code de réduction supprimé.');

        return $this->redirect('/admin/promotions');
    }
}
