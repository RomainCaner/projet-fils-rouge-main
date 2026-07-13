<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
?>

<?php if ($slides !== []): ?>
<section class="carousel" data-carousel aria-label="<?= e(t('home.carousel')) ?>" aria-roledescription="carousel">
    <div class="carousel__track">
        <?php foreach ($slides as $slide): ?>
            <div class="carousel__slide">
                <img src="<?= e(image_url($slide['image'])) ?>" alt="<?= e((string) $slide['title']) ?>">
                <div class="carousel__caption">
                    <h2><?= e((string) $slide['title']) ?></h2>
                    <?php if (!empty($slide['subtitle'])): ?><p><?= e((string) $slide['subtitle']) ?></p><?php endif; ?>
                    <?php if (!empty($slide['link_url'])): ?>
                        <a class="btn btn--primary" href="<?= e(url((string) $slide['link_url'])) ?>"><?= e(t('home.discover')) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="carousel__btn carousel__btn--prev" aria-label="<?= e(t('home.previous_slide')) ?>">‹</button>
    <button type="button" class="carousel__btn carousel__btn--next" aria-label="<?= e(t('home.next_slide')) ?>">›</button>
</section>
<?php endif; ?>

<?php if ($introText !== ''): ?>
    <section class="card mb-4"><p style="margin:0"><?= e($introText) ?></p></section>
<?php endif; ?>

<section>
    <h2 class="section-title"><?= e(t('home.categories')) ?></h2>
    <div class="category-grid">
        <?php foreach ($categories as $category): ?>
            <a class="category-card" href="<?= e(url('/categorie/' . $category['slug'])) ?>">
                <img src="<?= e(image_url($category['image'])) ?>" alt="<?= e((string) $category['name']) ?>" loading="lazy">
                <span><?= e((string) $category['name']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($featured !== []): ?>
<section>
    <h2 class="section-title"><?= e($featuredTitle) ?></h2>
    <div class="product-grid">
        <?php foreach ($featured as $product): ?>
            <?php $this->insert('partials/product_card', ['product' => $product]); ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
