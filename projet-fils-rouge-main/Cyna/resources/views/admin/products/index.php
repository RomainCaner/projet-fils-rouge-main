<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/admin', ['title' => $title]);

// Génère un lien d'en-tête triable (inverse la direction si déjà actif).
$sortLink = function (string $column, string $label) use ($sort, $dir, $q): string {
    $nextDir = ($sort === $column && $dir === 'asc') ? 'desc' : 'asc';
    $arrow = $sort === $column ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
    $url = url('/admin/produits?' . http_build_query(['sort' => $column, 'dir' => $nextDir, 'q' => $q]));

    return '<a class="sort" href="' . e($url) . '">' . e($label) . $arrow . '</a>';
};
?>

<div class="admin-toolbar">
    <form action="<?= e(url('/admin/produits')) ?>" method="get" class="admin-search">
        <label class="visually-hidden" for="q">Rechercher</label>
        <input id="q" type="search" name="q" value="<?= e((string) $q) ?>" placeholder="Rechercher un produit...">
        <button class="btn btn--ghost">Rechercher</button>
    </form>
    <a class="btn btn--primary" href="<?= e(url('/admin/produits/nouveau')) ?>">Nouveau produit</a>
</div>

<form action="<?= e(url('/admin/produits/suppression')) ?>" method="post">
    <?= csrf_field() ?>
    <div class="table-wrap card">
        <table class="data">
            <thead>
                <tr>
                    <th><input type="checkbox" data-select-all aria-label="Tout sélectionner"></th>
                    <th><?= $sortLink('name', 'Nom') ?></th>
                    <th>Catégorie</th>
                    <th><?= $sortLink('price_monthly_cents', 'Prix / mois') ?></th>
                    <th><?= $sortLink('priority', 'Priorité') ?></th>
                    <th><?= $sortLink('availability', 'Disponibilité') ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?= (int) $product['id'] ?>" aria-label="Sélectionner <?= e((string) $product['name']) ?>"></td>
                        <td><?= e((string) $product['name']) ?></td>
                        <td><?= e((string) $product['category_name']) ?></td>
                        <td><?= e(money((int) $product['price_monthly_cents'])) ?></td>
                        <td><?= (int) $product['priority'] ?></td>
                        <td>
                            <?php if ($product['availability'] === 'available'): ?>
                                <span class="badge badge--success">Disponible</span>
                            <?php else: ?>
                                <span class="badge badge--warning">Maintenance</span>
                            <?php endif; ?>
                        </td>
                        <td><a href="<?= e(url('/admin/produits/' . $product['id'])) ?>">Modifier</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($products === []): ?>
                    <tr><td colspan="7" class="text-muted">Aucun produit.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <button type="submit" class="btn btn--danger mt-4" data-confirm="Supprimer les produits sélectionnés ?">Supprimer la sélection</button>
</form>

<?php $this->insert('partials/pagination', ['paginator' => $paginator]); ?>

<?php $this->section('scripts'); ?>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
<?php $this->endSection(); ?>
