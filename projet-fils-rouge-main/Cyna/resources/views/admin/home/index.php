<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/admin', ['title' => $title]);

$slideFields = function (array $s = []) {
    $get = static fn (string $k) => e((string) ($s[$k] ?? ''));
    ?>
    <div class="field"><label>Titre<input name="title" value="<?= $get('title') ?>" required></label></div>
    <div class="field"><label>Sous-titre<input name="subtitle" value="<?= $get('subtitle') ?>"></label></div>
    <div class="field"><label>Lien (URL interne, ex: /categorie/soc)<input name="link_url" value="<?= $get('link_url') ?>"></label></div>
    <div class="field"><label>Position<input name="position" type="number" value="<?= $get('position') ?: '0' ?>"></label></div>
    <div class="field"><label><input type="checkbox" name="is_active" value="1" <?= ($s['is_active'] ?? 1) ? 'checked' : '' ?>> Active</label></div>
    <div class="field"><label>Image<input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label></div>
    <?php
};
?>

<section class="card mb-4">
    <h2>Réglages éditoriaux</h2>
    <form action="<?= e(url('/admin/accueil/reglages')) ?>" method="post">
        <?= csrf_field() ?>
        <div class="field"><label for="home_intro_text">Texte d'introduction</label><textarea id="home_intro_text" name="home_intro_text"><?= e($introText) ?></textarea></div>
        <div class="field"><label for="home_featured_title">Titre « Top produits »</label><input id="home_featured_title" name="home_featured_title" value="<?= e($featuredTitle) ?>"></div>
        <div class="field"><label for="pagination_per_page">Éléments par page (catalogue)</label><input id="pagination_per_page" name="pagination_per_page" type="number" min="1" value="<?= e($perPage) ?>"></div>
        <button class="btn btn--primary">Enregistrer</button>
    </form>
</section>

<section>
    <h2>Carrousel</h2>
    <?php foreach ($slides as $slide): ?>
        <div class="card mb-4">
            <strong><?= e((string) $slide['title']) ?></strong>
            <?php if (!$slide['is_active']): ?><span class="badge badge--muted">Inactive</span><?php endif; ?>
            <span class="text-muted">(position <?= (int) $slide['position'] ?>)</span>
            <details class="mt-4">
                <summary>Modifier</summary>
                <form action="<?= e(url('/admin/accueil/slides/' . $slide['id'])) ?>" method="post" enctype="multipart/form-data" class="mt-4">
                    <?= csrf_field() ?>
                    <?php $slideFields($slide); ?>
                    <?php if (!empty($slide['image'])): ?><p><img src="<?= e(image_url($slide['image'])) ?>" alt="" style="max-width:160px;border-radius:6px"></p><?php endif; ?>
                    <button class="btn btn--primary">Enregistrer</button>
                </form>
            </details>
            <form action="<?= e(url('/admin/accueil/slides/' . $slide['id'] . '/supprimer')) ?>" method="post" data-confirm="Supprimer cette slide ?" class="mt-4">
                <?= csrf_field() ?><button class="link-button" style="color:var(--color-danger)">Supprimer</button>
            </form>
        </div>
    <?php endforeach; ?>

    <details class="card">
        <summary><strong>Ajouter une slide</strong></summary>
        <form action="<?= e(url('/admin/accueil/slides')) ?>" method="post" enctype="multipart/form-data" class="mt-4">
            <?= csrf_field() ?>
            <?php $slideFields(); ?>
            <button class="btn btn--primary">Ajouter</button>
        </form>
    </details>
</section>

<?php $this->section('scripts'); ?>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
<?php $this->endSection(); ?>
