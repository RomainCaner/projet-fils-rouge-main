<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
?>
<article class="card" style="max-width:760px;margin-inline:auto">
    <h1><?= e(t('pages.terms')) ?></h1>
    <p><?= e(t('pages.terms_intro')) ?></p>
    <h2><?= e(t('pages.terms_object')) ?></h2>
    <p class="text-muted"><?= e(t('pages.placeholder')) ?></p>
    <h2><?= e(t('pages.terms_subscriptions')) ?></h2>
    <p class="text-muted"><?= e(t('pages.placeholder')) ?></p>
</article>
