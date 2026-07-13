<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
$available = $product['availability'] === 'available';
?>

<nav class="text-muted mb-4" aria-label="<?= e(t('app.breadcrumb')) ?>">
    <a href="<?= e(url('/categorie/' . $product['category_slug'])) ?>"><?= e((string) $product['category_name']) ?></a>
    / <?= e((string) $product['name']) ?>
</nav>

<div class="product-detail">
    <div class="product-detail__media">
        <?php if ($images !== []): ?>
            <div class="carousel" data-carousel aria-label="<?= e(t('product.illustrations')) ?>">
                <div class="carousel__track">
                    <?php foreach ($images as $image): ?>
                        <div class="carousel__slide"><img src="<?= e(image_url($image['path'])) ?>" alt="<?= e((string) ($image['alt'] ?? $product['name'])) ?>"></div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="carousel__btn carousel__btn--prev" aria-label="<?= e(t('home.previous_slide')) ?>">‹</button>
                <button type="button" class="carousel__btn carousel__btn--next" aria-label="<?= e(t('home.next_slide')) ?>">›</button>
            </div>
        <?php else: ?>
            <img src="<?= e(image_url($product['image'])) ?>" alt="<?= e((string) $product['name']) ?>">
        <?php endif; ?>
    </div>

    <div class="product-detail__info">
        <h1><?= e((string) $product['name']) ?></h1>

        <?php if ($available): ?>
            <p class="badge badge--success"><?= e(t('product.available')) ?></p>
        <?php else: ?>
            <p class="badge badge--warning"><?= e(t('product.maintenance')) ?></p>
        <?php endif; ?>

        <p class="product-detail__price"><?= e(money((int) $product['price_monthly_cents'])) ?> <span class="text-muted" style="font-size:1rem"><?= e(t('product.per_month')) ?></span></p>
        <p class="text-muted"><?= e(t('product.or_annual', ['price' => money((int) $product['price_annual_cents'])])) ?></p>

        <p><?= nl2br(e((string) $product['description'])) ?></p>

        <form action="<?= e(url('/panier/ajouter')) ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <div class="field">
                <label for="period"><?= e(t('product.billing_period')) ?></label>
                <select id="period" name="period">
                    <option value="monthly"><?= e(t('product.monthly', ['price' => money((int) $product['price_monthly_cents'])])) ?></option>
                    <option value="annual"><?= e(t('product.annual', ['price' => money((int) $product['price_annual_cents'])])) ?></option>
                </select>
            </div>
            <div class="field">
                <label for="quantity"><?= e(t('product.quantity')) ?></label>
                <input id="quantity" type="number" name="quantity" value="1" min="1" max="999">
            </div>
            <button type="submit" class="btn btn--primary btn--block" <?= $available ? '' : 'disabled' ?>>
                <?= $available ? e(t('product.subscribe')) : e(t('product.unavailable')) ?>
            </button>
        </form>

        <?php if (!empty($product['tech_specs'])): ?>
            <h2 class="section-title"><?= e(t('product.tech_specs')) ?></h2>
            <ul class="specs">
                <?php foreach (array_filter(array_map('trim', explode(';', (string) $product['tech_specs']))) as $spec): ?>
                    <li><?= e($spec) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php if ($similar !== []): ?>
<section>
    <h2 class="section-title"><?= e(t('product.similar')) ?></h2>
    <div class="product-grid">
        <?php foreach ($similar as $item): ?>
            <?php $this->insert('partials/product_card', ['product' => $item]); ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
