<?php /** @var \Cyna\Core\View $this */ ?>
<!DOCTYPE html>
<html lang="<?= e(locale()) ?>" dir="<?= e(dir_attr()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Cyna') ?> — Cyna</title>
    <meta name="description" content="<?= e(t('app.tagline')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/design-system.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <?= $this->yieldSection('head') ?>
</head>
<body>
<a class="skip-link" href="#contenu"><?= e(t('app.skip_to_content')) ?></a>

<header class="site-header">
    <div class="container site-header__inner">
        <a href="<?= e(url('/')) ?>" class="logo" aria-label="Cyna — <?= e(t('app.home')) ?>">Cyna</a>

        <form class="search" action="<?= e(url('/recherche')) ?>" method="get" role="search">
            <label for="search-input" class="visually-hidden"><?= e(t('app.search')) ?></label>
            <input id="search-input" type="search" name="q" placeholder="<?= e(t('app.search_placeholder')) ?>" value="<?= e((string) ($_GET['q'] ?? '')) ?>">
            <button type="submit" aria-label="<?= e(t('app.search')) ?>">⌕</button>
        </form>

        <div class="site-header__actions">
            <a href="<?= e(url('/panier')) ?>" class="cart-link" aria-label="<?= e(t('app.cart')) ?>">
                🛒<?php if (cart_count() > 0): ?><span class="cart-badge" aria-hidden="true"></span><span class="visually-hidden"><?= cart_count() ?></span><?php endif; ?>
            </a>
            <button type="button" class="burger" aria-expanded="false" aria-controls="main-nav" aria-label="<?= e(t('app.menu')) ?>">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <nav id="main-nav" class="main-nav" aria-label="<?= e(t('app.main_nav')) ?>">
        <ul class="container main-nav__list">
            <li><a href="<?= e(url('/')) ?>"><?= e(t('menu.home')) ?></a></li>
            <?php foreach (nav_categories() as $navCategory): ?>
                <?php $navLabel = preg_split('/\s+[—–-]\s+/u', (string) $navCategory['name'])[0]; ?>
                <li><a href="<?= e(url('/categorie/' . $navCategory['slug'])) ?>"><?= e($navLabel) ?></a></li>
            <?php endforeach; ?>
            <li><a href="<?= e(url('/recherche')) ?>"><?= e(t('menu.catalog')) ?></a></li>
            <li><a href="<?= e(url('/contact')) ?>"><?= e(t('menu.contact')) ?></a></li>
            <li><a href="<?= e(url('/a-propos')) ?>"><?= e(t('menu.about')) ?></a></li>

            <li class="main-nav__sep" aria-hidden="true"></li>

            <?php if (auth_user() !== null): ?>
                <li><a href="<?= e(url('/compte')) ?>"><?= e(t('menu.account')) ?></a></li>
                <li>
                    <form action="<?= e(url('/deconnexion')) ?>" method="post"><?= csrf_field() ?>
                        <button type="submit" class="link-button"><?= e(t('menu.logout')) ?></button>
                    </form>
                </li>
            <?php else: ?>
                <li><a href="<?= e(url('/connexion')) ?>"><?= e(t('menu.login')) ?></a></li>
                <li><a class="btn btn--primary btn--nav" href="<?= e(url('/inscription')) ?>"><?= e(t('menu.register')) ?></a></li>
            <?php endif; ?>
            <li class="lang-switch" aria-label="<?= e(t('app.language')) ?>">
                <a href="<?= e(url('/langue/fr')) ?>" <?= locale() === 'fr' ? 'aria-current="true"' : '' ?>>FR</a>
                <a href="<?= e(url('/langue/en')) ?>" <?= locale() === 'en' ? 'aria-current="true"' : '' ?>>EN</a>
            </li>
        </ul>
    </nav>
</header>

<?php $this->insert('partials/flash'); ?>

<main id="contenu" class="container main-content">
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container site-footer__inner">
        <nav aria-label="<?= e(t('footer.nav')) ?>">
            <a href="<?= e(url('/mentions-legales')) ?>"><?= e(t('menu.legal')) ?></a>
            <a href="<?= e(url('/cgu')) ?>"><?= e(t('menu.terms')) ?></a>
            <a href="<?= e(url('/confidentialite')) ?>"><?= e(t('menu.privacy')) ?></a>
            <a href="<?= e(url('/contact')) ?>"><?= e(t('menu.contact')) ?></a>
        </nav>
        <p class="site-footer__legal">© <?= date('Y') ?> Cyna-IT — <?= e(t('footer.rights')) ?></p>
    </div>
</footer>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?= $this->yieldSection('scripts') ?>
</body>
</html>
