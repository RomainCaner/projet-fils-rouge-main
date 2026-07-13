<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
?>
<article class="card" style="max-width:760px;margin-inline:auto">
    <h1><?= e(t('pages.about')) ?></h1>
    <p><?= e(t('pages.about_text')) ?></p>
</article>
