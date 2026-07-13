<?php

declare(strict_types=1);

namespace Cyna\Controllers\Front;

use Cyna\Core\Controller;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Core\Session;
use Cyna\Services\Auth;
use Cyna\Services\Cart;
use Cyna\Services\Promotion;

/**
 * Panier accessible à tous (connectés ou non) : ajout, modification de la
 * quantité ou de la périodicité, suppression, application d'un code de
 * réduction, et calcul du total en direct.
 */
final class CartController extends Controller
{
    public function show(Request $request): Response
    {
        $subtotal = Cart::total();
        $promo = Promotion::resolve($subtotal);

        return $this->view('front/cart', [
            'title'          => t('cart.title'),
            'lines'          => Cart::lines(),
            'subtotal'       => $subtotal,
            'discount'       => $promo['discount'],
            'promoCode'      => $promo['code'],
            'promoTitle'     => $promo['title'],
            'total'          => $promo['total'],
            'hasUnavailable' => Cart::hasUnavailable(),
            'isLoggedIn'     => Auth::check(),
        ]);
    }

    public function applyCode(Request $request): Response
    {
        $result = Promotion::apply($request->string('code'));

        Session::flash(
            $result['ok'] ? 'success' : 'error',
            $result['ok'] ? t('promo.applied') : t($result['error'] ?? 'promo.invalid'),
        );

        return $this->redirect('/panier');
    }

    public function removeCode(Request $request): Response
    {
        Promotion::clear();
        Session::flash('success', t('promo.removed'));

        return $this->redirect('/panier');
    }

    public function add(Request $request): Response
    {
        Cart::add($request->int('product_id'), $request->string('period', 'monthly'), max(1, $request->int('quantity', 1)));
        Session::flash('success', t('cart.added'));

        return $this->redirect('/panier');
    }

    public function update(Request $request): Response
    {
        Cart::updateQuantity($request->int('product_id'), $request->string('period'), $request->int('quantity'));

        return $this->redirect('/panier');
    }

    public function remove(Request $request): Response
    {
        Cart::remove($request->int('product_id'), $request->string('period'));
        Session::flash('success', t('cart.removed'));

        return $this->redirect('/panier');
    }
}
