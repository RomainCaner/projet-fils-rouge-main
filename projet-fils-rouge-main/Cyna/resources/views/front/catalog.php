<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
?>

<section class="category-card mb-4" style="min-height:200px">
    <img src="<?= e(image_url($category['image'])) ?>" alt="<?= e((string) $category['name']) ?>" style="height:200px">
    <span style="font-size:1.4rem"><?= e((string) $category['name']) ?></span>
</section>

<?php if (!empty($category['description'])): ?>
    <p class="text-muted"><?= e((string) $category['description']) ?></p>
<?php endif; ?>

<?php if ($products === []): ?>
    <p><?= e(t('catalog.empty')) ?></p>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <?php $this->insert('partials/product_card', ['product' => $product]); ?>
        <?php endforeach; ?>
    </div>
    <?php $this->insert('partials/pagination', ['paginator' => $paginator]); ?>
<?php endif; ?>
