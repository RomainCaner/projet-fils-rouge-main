<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
?>
<article class="card" style="max-width:760px;margin-inline:auto">
    <h1><?= e(t('pages.legal')) ?></h1>
    <p><strong>Cyna-IT</strong> — 10 rue de Penthièvre, 75008 Paris — SIRET : 913 711 032 00015</p>
    <p><?= e(t('pages.legal_editor')) ?></p>
    <p class="text-muted"><?= e(t('pages.legal_host')) ?></p>
</article>
