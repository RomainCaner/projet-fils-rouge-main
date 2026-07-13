<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
?>
<article class="card" style="max-width:760px;margin-inline:auto">
    <h1><?= e(t('pages.privacy')) ?></h1>
    <p><?= e(t('pages.privacy_intro')) ?></p>
    <h2><?= e(t('pages.privacy_data')) ?></h2>
    <p class="text-muted"><?= e(t('pages.privacy_data_text')) ?></p>
    <h2><?= e(t('pages.privacy_rights')) ?></h2>
    <p class="text-muted"><?= e(t('pages.privacy_rights_text')) ?></p>
</article>
