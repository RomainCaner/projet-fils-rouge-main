<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/admin', ['title' => $title]);
?>

<div class="admin-cards">
    <div class="card"><p class="text-muted">Chiffre d'affaires (<?= e($periods[$days]) ?>)</p><p class="admin-kpi"><?= e(money($summary['revenue'])) ?></p></div>
    <div class="card"><p class="text-muted">Commandes</p><p class="admin-kpi"><?= (int) $summary['orders'] ?></p></div>
    <div class="card"><p class="text-muted">Panier moyen</p><p class="admin-kpi"><?= e(money($summary['avg_basket'])) ?></p></div>
    <div class="card"><p class="text-muted">Nouveaux messages</p><p class="admin-kpi"><?= (int) $newMessages ?></p></div>
</div>

<div class="admin-period">
    <span>Période :</span>
    <?php foreach ($periods as $value => $label): ?>
        <a class="btn <?= $days === $value ? 'btn--primary' : 'btn--ghost' ?>" href="<?= e(url('/admin?period=' . $value)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<div class="admin-charts" data-stats data-endpoint="<?= e(url('/admin/statistiques?period=' . $days)) ?>">
    <section class="card">
        <h2>Ventes par jour</h2>
        <canvas data-chart="bar" height="240" role="img" aria-label="Histogramme des ventes par jour"></canvas>
    </section>
    <section class="card">
        <h2>Répartition par catégorie</h2>
        <canvas data-chart="pie" height="240" role="img" aria-label="Camembert des ventes par catégorie"></canvas>
    </section>
</div>

<?php $this->section('scripts'); ?>
<script src="<?= e(asset('js/charts.js')) ?>" defer></script>
<?php $this->endSection(); ?>
