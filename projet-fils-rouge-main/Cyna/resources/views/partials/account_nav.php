<?php /** Navigation latérale de l'espace client. */ ?>
<nav aria-label="<?= e(t('account.nav')) ?>">
    <ul class="account-nav">
        <li><a href="<?= e(url('/compte')) ?>"><?= e(t('account.title')) ?></a></li>
        <li><a href="<?= e(url('/compte/parametres')) ?>"><?= e(t('account.settings')) ?></a></li>
        <li><a href="<?= e(url('/compte/adresses')) ?>"><?= e(t('account.addresses')) ?></a></li>
        <li><a href="<?= e(url('/compte/abonnements')) ?>"><?= e(t('account.subscriptions')) ?></a></li>
        <li><a href="<?= e(url('/compte/commandes')) ?>"><?= e(t('account.orders')) ?></a></li>
    </ul>
</nav>
