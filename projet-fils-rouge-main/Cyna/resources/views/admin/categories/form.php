<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/admin', ['title' => $title]);
$isEdit = $category !== null;
$action = $isEdit ? url('/admin/categories/' . $category['id']) : url('/admin/categories');
$val = static fn (string $key, $fallback = '') => e((string) old($key, (string) ($category[$key] ?? $fallback)));
?>

<form action="<?= e($action) ?>" method="post" enctype="multipart/form-data" style="max-width:640px">
    <?= csrf_field() ?>
    <div class="card mb-4">
        <div class="field <?= error('name') ? 'field--invalid' : '' ?>">
            <label for="name">Nom</label>
            <input id="name" name="name" value="<?= $val('name') ?>" required>
            <?php if (error('name')): ?><p class="field__error"><?= e(error('name')) ?></p><?php endif; ?>
        </div>
        <div class="field"><label for="slug">Slug (laisser vide pour générer)</label><input id="slug" name="slug" value="<?= $val('slug') ?>"></div>
        <div class="field"><label for="description">Description</label><textarea id="description" name="description"><?= $val('description') ?></textarea></div>
        <div class="field"><label for="position">Position</label><input id="position" name="position" type="number" value="<?= $val('position', '0') ?>"></div>
        <div class="field">
            <label for="image">Image</label>
            <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
            <?php if ($isEdit && !empty($category['image'])): ?>
                <p class="mt-4"><img src="<?= e(image_url($category['image'])) ?>" alt="" style="max-width:120px;border-radius:6px"></p>
            <?php endif; ?>
        </div>
    </div>
    <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Enregistrer' : 'Créer' ?></button>
    <a class="btn btn--ghost" href="<?= e(url('/admin/categories')) ?>">Annuler</a>
</form>
