<?php
/**
 * Carte produit réutilisée dans les grilles (accueil, catalogue, recherche).
 * @var array<string,mixed> $product
 */
$available = ($product['availability'] ?? 'available') === 'available';
?>
<article class="product-card <?= $available ? '' : 'product-card--unavailable' ?>">
    <a href="<?= e(url('/service/' . $product['slug'])) ?>" class="product-card__link">
        <img class="product-card__image" src="<?= e(image_url($product['image'] ?? null)) ?>" alt="<?= e((string) $product['name']) ?>" loading="lazy">
        <h3 class="product-card__name"><?= e((string) $product['name']) ?></h3>
        <p class="product-card__price"><?= e(money((int) $product['price_monthly_cents'])) ?> <span><?= e(t('product.per_month')) ?></span></p>
        <?php if (!$available): ?>
            <p class="badge badge--muted"><?= e(t('product.unavailable')) ?></p>
        <?php endif; ?>
    </a>
</article>
