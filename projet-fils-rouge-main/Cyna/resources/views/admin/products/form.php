<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/admin', ['title' => $title]);
$isEdit = $product !== null;
$action = $isEdit ? url('/admin/produits/' . $product['id']) : url('/admin/produits');
$val = static fn (string $key, $fallback = '') => e((string) old($key, (string) ($product[$key] ?? $fallback)));
$priceVal = static fn (string $key) => $product ? number_format(((int) $product[$key]) / 100, 2, '.', '') : old(str_replace('_cents', '', $key));
?>

<form action="<?= e($action) ?>" method="post" enctype="multipart/form-data" style="max-width:760px">
    <?= csrf_field() ?>

    <div class="card mb-4">
        <div class="field <?= error('name') ? 'field--invalid' : '' ?>">
            <label for="name">Nom</label>
            <input id="name" name="name" value="<?= $val('name') ?>" required>
            <?php if (error('name')): ?><p class="field__error"><?= e(error('name')) ?></p><?php endif; ?>
        </div>
        <div class="field">
            <label for="slug">Slug (laisser vide pour générer)</label>
            <input id="slug" name="slug" value="<?= $val('slug') ?>">
        </div>
        <div class="field">
            <label for="category_id">Catégorie</label>
            <select id="category_id" name="category_id" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= (int) ($product['category_id'] ?? old('category_id')) === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= e((string) $category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field <?= error('short_description') ? 'field--invalid' : '' ?>">
            <label for="short_description">Description courte</label>
            <input id="short_description" name="short_description" value="<?= $val('short_description') ?>" maxlength="255" required>
        </div>
        <div class="field <?= error('description') ? 'field--invalid' : '' ?>">
            <label for="description">Description</label>
            <textarea id="description" name="description" required><?= $val('description') ?></textarea>
        </div>
        <div class="field">
            <label for="tech_specs">Caractéristiques techniques (séparées par « ; »)</label>
            <textarea id="tech_specs" name="tech_specs"><?= $val('tech_specs') ?></textarea>
        </div>
    </div>

    <div class="card mb-4">
        <div class="field"><label for="price_monthly">Prix mensuel (€)</label><input id="price_monthly" name="price_monthly" type="number" step="0.01" min="0" value="<?= e((string) $priceVal('price_monthly_cents')) ?>" required></div>
        <div class="field"><label for="price_annual">Prix annuel (€)</label><input id="price_annual" name="price_annual" type="number" step="0.01" min="0" value="<?= e((string) $priceVal('price_annual_cents')) ?>" required></div>
        <div class="field">
            <label for="availability">Disponibilité</label>
            <select id="availability" name="availability">
                <option value="available" <?= ($product['availability'] ?? 'available') === 'available' ? 'selected' : '' ?>>Disponible</option>
                <option value="maintenance" <?= ($product['availability'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
            </select>
        </div>
        <div class="field"><label for="priority">Priorité (tri catalogue)</label><input id="priority" name="priority" type="number" value="<?= $val('priority', '0') ?>"></div>
        <div class="field">
            <label><input type="checkbox" name="is_featured" value="1" <?= ($product['is_featured'] ?? 0) ? 'checked' : '' ?>> Mettre en avant (Top produits)</label>
        </div>
        <div class="field"><label for="featured_position">Position dans les top produits</label><input id="featured_position" name="featured_position" type="number" value="<?= $val('featured_position', '0') ?>"></div>
    </div>

    <div class="card mb-4">
        <div class="field">
            <label for="image">Image principale</label>
            <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
            <?php if ($isEdit && !empty($product['image'])): ?>
                <p class="text-muted mt-4">Image actuelle : <img src="<?= e(image_url($product['image'])) ?>" alt="" style="max-width:120px;border-radius:6px;display:inline-block"></p>
            <?php endif; ?>
        </div>
    </div>

    <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Enregistrer' : 'Créer le produit' ?></button>
    <a class="btn btn--ghost" href="<?= e(url('/admin/produits')) ?>">Annuler</a>
</form>
