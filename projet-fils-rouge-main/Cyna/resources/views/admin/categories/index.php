<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/admin', ['title' => $title]);
?>

<div class="admin-toolbar">
    <span class="text-muted">L'ordre d'affichage est défini par le champ « position ».</span>
    <a class="btn btn--primary" href="<?= e(url('/admin/categories/nouveau')) ?>">Nouvelle catégorie</a>
</div>

<div class="table-wrap card">
    <table class="data">
        <thead><tr><th>Position</th><th>Nom</th><th>Slug</th><th>Produits</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?= (int) $category['position'] ?></td>
                    <td><?= e((string) $category['name']) ?></td>
                    <td><code><?= e((string) $category['slug']) ?></code></td>
                    <td><?= (int) $category['product_count'] ?></td>
                    <td style="display:flex;gap:.5rem">
                        <a href="<?= e(url('/admin/categories/' . $category['id'])) ?>">Modifier</a>
                        <form action="<?= e(url('/admin/categories/' . $category['id'] . '/supprimer')) ?>" method="post" data-confirm="Supprimer cette catégorie et ses produits ?">
                            <?= csrf_field() ?><button class="link-button" style="color:var(--color-danger)">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($categories === []): ?><tr><td colspan="5" class="text-muted">Aucune catégorie.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php $this->section('scripts'); ?>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
<?php $this->endSection(); ?>
