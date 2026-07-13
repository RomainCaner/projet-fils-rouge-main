<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
$selectedCategories = $filters['categories'] ?? [];
?>

<h1><?= e(t('search.title')) ?></h1>

<div class="layout-sidebar">
    <aside>
        <form class="card" action="<?= e(url('/recherche')) ?>" method="get">
            <div class="field">
                <label for="q"><?= e(t('search.keyword')) ?></label>
                <input id="q" type="search" name="q" value="<?= e((string) $filters['q']) ?>">
            </div>

            <fieldset>
                <legend><?= e(t('search.categories')) ?></legend>
                <?php foreach ($categories as $category): ?>
                    <label style="font-weight:400">
                        <input type="checkbox" name="categories[]" value="<?= (int) $category['id'] ?>"
                            <?= in_array((int) $category['id'], $selectedCategories, true) ? 'checked' : '' ?>>
                        <?= e((string) $category['name']) ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <div class="field">
                <label for="price_min"><?= e(t('search.price_min')) ?></label>
                <input id="price_min" type="number" name="price_min" min="0" step="0.01"
                    value="<?= $filters['price_min'] !== null ? e((string) ($filters['price_min'] / 100)) : '' ?>">
            </div>
            <div class="field">
                <label for="price_max"><?= e(t('search.price_max')) ?></label>
                <input id="price_max" type="number" name="price_max" min="0" step="0.01"
                    value="<?= $filters['price_max'] !== null ? e((string) ($filters['price_max'] / 100)) : '' ?>">
            </div>

            <div class="field">
                <label style="font-weight:400">
                    <input type="checkbox" name="available_only" value="1" <?= $filters['available_only'] ? 'checked' : '' ?>>
                    <?= e(t('search.available_only')) ?>
                </label>
            </div>

            <div class="field">
                <label for="sort"><?= e(t('search.sort')) ?></label>
                <select id="sort" name="sort">
                    <option value=""><?= e(t('search.relevance')) ?></option>
                    <option value="price" <?= $filters['sort'] === 'price' ? 'selected' : '' ?>><?= e(t('search.sort_price')) ?></option>
                    <option value="newness" <?= $filters['sort'] === 'newness' ? 'selected' : '' ?>><?= e(t('search.sort_newness')) ?></option>
                    <option value="availability" <?= $filters['sort'] === 'availability' ? 'selected' : '' ?>><?= e(t('search.sort_availability')) ?></option>
                </select>
            </div>
            <div class="field">
                <label for="dir"><?= e(t('search.direction')) ?></label>
                <select id="dir" name="dir">
                    <option value="asc" <?= $filters['dir'] === 'asc' ? 'selected' : '' ?>><?= e(t('search.asc')) ?></option>
                    <option value="desc" <?= $filters['dir'] === 'desc' ? 'selected' : '' ?>><?= e(t('search.desc')) ?></option>
                </select>
            </div>

            <button type="submit" class="btn btn--primary btn--block"><?= e(t('search.apply')) ?></button>
        </form>
    </aside>

    <div>
        <?php if ($products === []): ?>
            <p><?= e(t('search.no_result')) ?></p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php $this->insert('partials/product_card', ['product' => $product]); ?>
                <?php endforeach; ?>
            </div>
            <?php $this->insert('partials/pagination', ['paginator' => $paginator]); ?>
        <?php endif; ?>
    </div>
</div>
