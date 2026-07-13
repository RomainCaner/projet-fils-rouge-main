<?php

declare(strict_types=1);

use Cyna\Controllers\Admin\AuthController;
use Cyna\Controllers\Admin\CategoryController;
use Cyna\Controllers\Admin\ContactController;
use Cyna\Controllers\Admin\DashboardController;
use Cyna\Controllers\Admin\HomeController;
use Cyna\Controllers\Admin\OrderController;
use Cyna\Controllers\Admin\ProductController;
use Cyna\Controllers\Admin\PromotionController;
use Cyna\Controllers\Admin\SubscriptionController;
use Cyna\Controllers\Admin\UserController;
use Cyna\Core\Router;
use Cyna\Middleware\RequireAdmin;
use Cyna\Middleware\VerifyCsrf;

/**
 * Routes du back-office (réservé aux administrateurs authentifiés en 2FA).
 */
return static function (Router $router): void {
    $router->group('/admin', [VerifyCsrf::class], static function (Router $router): void {
        // --- Authentification administrateur (mot de passe + TOTP) ----------
        $router->get('/connexion', [AuthController::class, 'showLogin']);
        $router->post('/connexion', [AuthController::class, 'login']);
        $router->get('/2fa', [AuthController::class, 'showTwoFactor']);
        $router->post('/2fa', [AuthController::class, 'verifyTwoFactor']);
        $router->post('/deconnexion', [AuthController::class, 'logout']);

        // --- Espace d'administration ----------------------------------------
        $router->group('', [RequireAdmin::class], static function (Router $router): void {
            $router->get('', [DashboardController::class, 'index']);
            $router->get('/statistiques', [DashboardController::class, 'data']);

            // Produits (avec suppression multiple)
            $router->get('/produits', [ProductController::class, 'index']);
            $router->get('/produits/nouveau', [ProductController::class, 'create']);
            $router->post('/produits', [ProductController::class, 'store']);
            $router->get('/produits/{id}', [ProductController::class, 'edit']);
            $router->post('/produits/{id}', [ProductController::class, 'update']);
            $router->post('/produits/suppression', [ProductController::class, 'bulkDelete']);

            // Catégories
            $router->get('/categories', [CategoryController::class, 'index']);
            $router->get('/categories/nouveau', [CategoryController::class, 'create']);
            $router->post('/categories', [CategoryController::class, 'store']);
            $router->get('/categories/{id}', [CategoryController::class, 'edit']);
            $router->post('/categories/{id}', [CategoryController::class, 'update']);
            $router->post('/categories/{id}/supprimer', [CategoryController::class, 'delete']);

            // Codes de réduction (promotions)
            $router->get('/promotions', [PromotionController::class, 'index']);
            $router->post('/promotions', [PromotionController::class, 'store']);
            $router->post('/promotions/{id}/statut', [PromotionController::class, 'toggle']);
            $router->post('/promotions/{id}/supprimer', [PromotionController::class, 'delete']);

            // Abonnements (consultation)
            $router->get('/abonnements', [SubscriptionController::class, 'index']);

            // Commandes
            $router->get('/commandes', [OrderController::class, 'index']);
            $router->get('/commandes/export', [OrderController::class, 'export']);
            $router->get('/commandes/{id}', [OrderController::class, 'show']);
            $router->post('/commandes/{id}/statut', [OrderController::class, 'updateStatus']);

            // Utilisateurs
            $router->get('/utilisateurs', [UserController::class, 'index']);

            // Page d'accueil (carrousel + réglages)
            $router->get('/accueil', [HomeController::class, 'index']);
            $router->post('/accueil/reglages', [HomeController::class, 'updateSettings']);
            $router->post('/accueil/slides', [HomeController::class, 'storeSlide']);
            $router->post('/accueil/slides/{id}', [HomeController::class, 'updateSlide']);
            $router->post('/accueil/slides/{id}/supprimer', [HomeController::class, 'deleteSlide']);

            // Messages de contact
            $router->get('/messages', [ContactController::class, 'index']);
            $router->get('/messages/{id}', [ContactController::class, 'show']);
            $router->post('/messages/{id}/statut', [ContactController::class, 'updateStatus']);
        });
    });
};
