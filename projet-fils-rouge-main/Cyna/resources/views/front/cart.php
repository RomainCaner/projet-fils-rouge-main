<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
?>

<h1><?= e(t('cart.title')) ?></h1>

<?php if ($lines === []): ?>
    <p><?= e(t('cart.empty')) ?></p>
    <a class="btn btn--primary" href="<?= e(url('/')) ?>"><?= e(t('cart.browse')) ?></a>
<?php else: ?>
    <div class="card">
        <?php foreach ($lines as $line): $product = $line['product']; ?>
            <div class="cart-line">
                <img src="<?= e(image_url($product['image'])) ?>" alt="<?= e((string) $product['name']) ?>">
                <div>
                    <a href="<?= e(url('/service/' . $product['slug'])) ?>"><strong><?= e((string) $product['name']) ?></strong></a>
                    <p class="text-muted" style="margin:0">
                        <?= e($line['period'] === 'annual' ? t('product.period_annual') : t('product.period_monthly')) ?>
                        — <?= e(money($line['unit_price'])) ?>
                    </p>
                    <?php if (!$line['available']): ?>
                        <span class="badge badge--danger"><?= e(t('cart.unavailable')) ?></span>
                    <?php endif; ?>

                    <form class="qty-form mt-4" action="<?= e(url('/panier/modifier')) ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                        <input type="hidden" name="period" value="<?= e($line['period']) ?>">
                        <label class="visually-hidden" for="qty-<?= (int) $product['id'] ?>-<?= e($line['period']) ?>"><?= e(t('product.quantity')) ?></label>
                        <input id="qty-<?= (int) $product['id'] ?>-<?= e($line['period']) ?>" type="number" name="quantity" value="<?= $line['quantity'] ?>" min="0">
                        <button type="submit" class="btn btn--ghost"><?= e(t('cart.update')) ?></button>
                    </form>
                </div>
                <div style="text-align:end">
                    <strong><?= e(money($line['line_total'])) ?></strong>
                    <form action="<?= e(url('/panier/supprimer')) ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                        <input type="hidden" name="period" value="<?= e($line['period']) ?>">
                        <button type="submit" class="link-button"><?= e(t('cart.remove')) ?></button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($promoCode !== null): ?>
            <div class="cart-summary" style="border:0">
                <span><?= e(t('cart.subtotal')) ?></span>
                <span><?= e(money($subtotal)) ?></span>
            </div>
            <div class="cart-summary" style="border:0;color:var(--color-success,#0f9d6f)">
                <span>
                    <?= e(t('promo.discount')) ?>
                    <?php if (($promoTitle ?? null) !== null): ?><?= e($promoTitle) ?> <?php endif; ?>
                    (<strong><?= e($promoCode) ?></strong>)
                    <form action="<?= e(url('/panier/code/supprimer')) ?>" method="post" style="display:inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="link-button"><?= e(t('promo.remove_action')) ?></button>
                    </form>
                </span>
                <span>−<?= e(money($discount)) ?></span>
            </div>
        <?php endif; ?>

        <div class="cart-summary">
            <span><?= e(t('cart.total')) ?></span>
            <span><?= e(money($total)) ?></span>
        </div>

        <form class="promo-form mt-4" action="<?= e(url('/panier/code')) ?>" method="post"
              style="display:flex;gap:.5rem;align-items:center">
            <?= csrf_field() ?>
            <label class="visually-hidden" for="promo-code"><?= e(t('promo.label')) ?></label>
            <input id="promo-code" type="text" name="code" placeholder="<?= e(t('promo.placeholder')) ?>"
                   value="<?= e($promoCode ?? '') ?>" maxlength="40">
            <button type="submit" class="btn btn--ghost"><?= e(t('promo.apply_action')) ?></button>
        </form>

        <?php if ($hasUnavailable): ?>
            <p class="flash flash--error"><?= e(t('cart.unavailable_warning')) ?></p>
        <?php endif; ?>

        <?php if (!$isLoggedIn): ?>
            <p class="text-muted"><?= e(t('cart.login_reminder')) ?></p>
        <?php endif; ?>

        <a class="btn btn--primary btn--block <?= $hasUnavailable ? 'btn--disabled' : '' ?>"
           href="<?= e(url('/checkout')) ?>" <?= $hasUnavailable ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
            <?= e(t('cart.checkout')) ?>
        </a>
    </div>
<?php endif; ?>
