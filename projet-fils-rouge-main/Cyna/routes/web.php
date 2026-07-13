<?php

declare(strict_types=1);

use Cyna\Controllers\Front\AccountController;
use Cyna\Controllers\Front\AuthController;
use Cyna\Controllers\Front\CartController;
use Cyna\Controllers\Front\CatalogController;
use Cyna\Controllers\Front\CheckoutController;
use Cyna\Controllers\Front\ContactController;
use Cyna\Controllers\Front\HomeController;
use Cyna\Controllers\Front\LocaleController;
use Cyna\Controllers\Front\PageController;
use Cyna\Controllers\Front\ProductController;
use Cyna\Controllers\Front\SearchController;
use Cyna\Core\Router;
use Cyna\Middleware\Authenticate;
use Cyna\Middleware\RedirectIfAuthenticated;
use Cyna\Middleware\VerifyCsrf;

/**
 * Routes du front-office (site e-commerce public et espace client).
 * Le middleware VerifyCsrf protège toutes les requêtes POST.
 */
return static function (Router $router): void {
    $router->group('', [VerifyCsrf::class], static function (Router $router): void {
        // --- Pages publiques ------------------------------------------------
        $router->get('/', [HomeController::class, 'index']);
        $router->get('/categorie/{slug}', [CatalogController::class, 'show']);
        $router->get('/service/{slug}', [ProductController::class, 'show']);
        $router->get('/recherche', [SearchController::class, 'index']);
        $router->get('/contact', [ContactController::class, 'show']);
        $router->post('/contact', [ContactController::class, 'submit']);
        $router->get('/langue/{locale}', [LocaleController::class, 'switch']);

        // Pages statiques légales
        $router->get('/cgu', [PageController::class, 'terms']);
        $router->get('/mentions-legales', [PageController::class, 'legal']);
        $router->get('/confidentialite', [PageController::class, 'privacy']);
        $router->get('/a-propos', [PageController::class, 'about']);

        // --- Panier ---------------------------------------------------------
        $router->get('/panier', [CartController::class, 'show']);
        $router->post('/panier/ajouter', [CartController::class, 'add']);
        $router->post('/panier/modifier', [CartController::class, 'update']);
        $router->post('/panier/supprimer', [CartController::class, 'remove']);
        $router->post('/panier/code', [CartController::class, 'applyCode']);
        $router->post('/panier/code/supprimer', [CartController::class, 'removeCode']);

        // --- Authentification (réservée aux visiteurs non connectés) --------
        $router->group('', [RedirectIfAuthenticated::class], static function (Router $router): void {
            $router->get('/inscription', [AuthController::class, 'showRegister']);
            $router->post('/inscription', [AuthController::class, 'register']);
            $router->get('/connexion', [AuthController::class, 'showLogin']);
            $router->post('/connexion', [AuthController::class, 'login']);
            $router->get('/mot-de-passe-oublie', [AuthController::class, 'showForgot']);
            $router->post('/mot-de-passe-oublie', [AuthController::class, 'sendReset']);
            $router->get('/reinitialiser/{token}', [AuthController::class, 'showReset']);
            $router->post('/reinitialiser', [AuthController::class, 'resetPassword']);
        });
        $router->get('/confirmation/{token}', [AuthController::class, 'confirmEmail']);
        $router->post('/deconnexion', [AuthController::class, 'logout']);

        // --- Tunnel de commande (connexion possible en invité) --------------
        $router->get('/checkout', [CheckoutController::class, 'show']);
        $router->post('/checkout', [CheckoutController::class, 'process']);
        $router->get('/checkout/confirmation/{invoice}', [CheckoutController::class, 'confirmation']);

        // --- Espace client (pages privées) ----------------------------------
        $router->group('/compte', [Authenticate::class], static function (Router $router): void {
            $router->get('', [AccountController::class, 'dashboard']);
            $router->get('/parametres', [AccountController::class, 'settings']);
            $router->post('/parametres/profil', [AccountController::class, 'updateProfile']);
            $router->post('/parametres/mot-de-passe', [AccountController::class, 'updatePassword']);

            $router->get('/adresses', [AccountController::class, 'addresses']);
            $router->post('/adresses', [AccountController::class, 'storeAddress']);
            $router->post('/adresses/{id}/modifier', [AccountController::class, 'updateAddress']);
            $router->post('/adresses/{id}/supprimer', [AccountController::class, 'deleteAddress']);
            $router->post('/adresses/{id}/defaut', [AccountController::class, 'defaultAddress']);

            $router->get('/abonnements', [AccountController::class, 'subscriptions']);
            $router->post('/abonnements/{id}/renouveler', [AccountController::class, 'renewSubscription']);
            $router->post('/abonnements/{id}/resilier', [AccountController::class, 'cancelSubscription']);
            $router->post('/abonnements/{id}/reactiver', [AccountController::class, 'reactivateSubscription']);

            $router->get('/commandes', [AccountController::class, 'orders']);
            $router->get('/commandes/{id}', [AccountController::class, 'orderDetail']);
            $router->get('/commandes/{id}/facture', [AccountController::class, 'invoice']);
        });
    });
};
