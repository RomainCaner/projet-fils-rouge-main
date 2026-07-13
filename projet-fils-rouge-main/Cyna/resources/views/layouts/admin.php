<?php
/** @var \Cyna\Core\View $this */
$admin = \Cyna\Services\Auth::adminUser();
$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '';
$nav = [
    '/admin'             => 'Tableau de bord',
    '/admin/produits'    => 'Produits',
    '/admin/categories'  => 'Catégories',
    '/admin/promotions'  => 'Promotions',
    '/admin/abonnements' => 'Abonnements',
    '/admin/commandes'   => 'Commandes',
    '/admin/utilisateurs' => 'Utilisateurs',
    '/admin/accueil'     => 'Page d\'accueil',
    '/admin/messages'    => 'Messages',
];
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Admin') ?> — Cyna Admin</title>
    <link rel="stylesheet" href="<?= e(asset('css/design-system.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin">
<a class="skip-link" href="#contenu">Aller au contenu</a>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a href="<?= e(url('/admin')) ?>" class="logo">Cyna<span> Admin</span></a>
        <nav aria-label="Navigation back-office">
            <ul class="admin-nav">
                <?php foreach ($nav as $path => $label): ?>
                    <?php $active = $path === '/admin' ? $current === '/admin' : str_starts_with($current, $path); ?>
                    <li><a href="<?= e(url($path)) ?>" <?= $active ? 'aria-current="page"' : '' ?>><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <h1 class="admin-topbar__title"><?= e($title ?? '') ?></h1>
            <div class="admin-topbar__user">
                <?php if ($admin !== null): ?><span><?= e($admin->fullName) ?></span><?php endif; ?>
                <form action="<?= e(url('/admin/deconnexion')) ?>" method="post"><?= csrf_field() ?><button class="btn btn--ghost">Déconnexion</button></form>
            </div>
        </header>

        <?php $this->insert('partials/flash'); ?>

        <main id="contenu" class="admin-content">
            <?= $content ?>
        </main>
    </div>
</div>
<?= $this->yieldSection('scripts') ?>
</body>
</html>
