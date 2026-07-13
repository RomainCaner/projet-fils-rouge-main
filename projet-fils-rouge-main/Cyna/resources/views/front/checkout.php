<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
$default = $addresses[0] ?? null;
$val = static fn (string $key, string $fallback = '') => old($key, (string) ($default[$key] ?? $fallback));
?>

<h1><?= e(t('checkout.title')) ?></h1>

<ol class="steps">
    <li><?= e(t('checkout.step_account')) ?></li>
    <li><?= e(t('checkout.step_address')) ?></li>
    <li><?= e(t('checkout.step_payment')) ?></li>
    <li><?= e(t('checkout.step_confirm')) ?></li>
</ol>

<?php if ($hasUnavailable): ?>
    <p class="flash flash--error"><?= e(t('cart.unavailable_warning')) ?></p>
<?php endif; ?>

<div class="checkout-grid">
    <form class="card" id="checkout-form" action="<?= e(url('/checkout')) ?>" method="post"
          data-stripe-key="<?= e($stripeKey) ?>"
          data-client-secret="<?= e($clientSecret) ?>"
          data-return-url="<?= e(url('/checkout')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="payment_intent_id" value="<?= e($paymentIntentId) ?>">

        <?php if ($user === null): ?>
            <fieldset>
                <legend><?= e(t('checkout.step_account')) ?></legend>
                <p class="text-muted"><?= e(t('checkout.guest_hint')) ?>
                    <a href="<?= e(url('/connexion')) ?>"><?= e(t('menu.login')) ?></a>
                </p>
                <div class="field <?= error('email') ? 'field--invalid' : '' ?>">
                    <label for="email"><?= e(t('contact.email')) ?></label>
                    <input id="email" type="email" name="email" value="<?= old('email') ?>" required>
                    <?php if (error('email')): ?><p class="field__error"><?= e(error('email')) ?></p><?php endif; ?>
                </div>
            </fieldset>
        <?php endif; ?>

        <fieldset>
            <legend><?= e(t('checkout.billing_address')) ?></legend>
            <div class="field"><label for="first_name"><?= e(t('address.first_name')) ?></label><input id="first_name" name="first_name" value="<?= $val('first_name') ?>" required></div>
            <div class="field"><label for="last_name"><?= e(t('address.last_name')) ?></label><input id="last_name" name="last_name" value="<?= $val('last_name') ?>" required></div>
            <div class="field"><label for="line1"><?= e(t('address.line1')) ?></label><input id="line1" name="line1" value="<?= $val('line1') ?>" required></div>
            <div class="field"><label for="line2"><?= e(t('address.line2')) ?></label><input id="line2" name="line2" value="<?= $val('line2') ?>"></div>
            <div class="field"><label for="city"><?= e(t('address.city')) ?></label><input id="city" name="city" value="<?= $val('city') ?>" required></div>
            <div class="field"><label for="region"><?= e(t('address.region')) ?></label><input id="region" name="region" value="<?= $val('region') ?>"></div>
            <div class="field"><label for="postal_code"><?= e(t('address.postal_code')) ?></label><input id="postal_code" name="postal_code" value="<?= $val('postal_code') ?>" required></div>
            <div class="field"><label for="country"><?= e(t('address.country')) ?></label><input id="country" name="country" value="<?= $val('country', 'France') ?>" required></div>
            <div class="field"><label for="phone"><?= e(t('address.phone')) ?></label><input id="phone" name="phone" value="<?= $val('phone') ?>"></div>
            <?php if ($user !== null): ?>
                <label style="font-weight:400"><input type="checkbox" name="save_address" value="1"> <?= e(t('checkout.save_address')) ?></label>
            <?php endif; ?>
        </fieldset>

        <fieldset>
            <legend><?= e(t('checkout.payment')) ?></legend>
            <p class="text-muted"><?= e(t('checkout.payment_secure')) ?></p>

            <?php if ($stripeConfigured): ?>
                <?php /* Le Payment Element (iframe Stripe) collecte la carte côté client : aucune donnée ne touche le serveur. */ ?>
                <div id="payment-element" class="field"></div>
            <?php else: ?>
                <p class="flash flash--info"><?= e(t('checkout.demo_mode')) ?></p>
            <?php endif; ?>

            <p id="payment-errors" class="field__error" role="alert" aria-live="polite"></p>
        </fieldset>

        <button type="submit" id="checkout-submit" class="btn btn--primary btn--block" <?= $hasUnavailable ? 'disabled' : '' ?>><?= e(t('checkout.confirm_purchase')) ?></button>
    </form>

    <aside class="card">
        <h2><?= e(t('checkout.summary')) ?></h2>
        <?php foreach ($lines as $line): ?>
            <div style="display:flex;justify-content:space-between;gap:1rem;padding-block:.5rem;border-block-end:1px solid var(--color-border)">
                <span><?= e((string) $line['product']['name']) ?> ×<?= $line['quantity'] ?><br>
                    <small class="text-muted"><?= e($line['period'] === 'annual' ? t('product.period_annual') : t('product.period_monthly')) ?></small></span>
                <span><?= e(money($line['line_total'])) ?></span>
            </div>
        <?php endforeach; ?>
        <?php if (($promoCode ?? null) !== null): ?>
            <div class="cart-summary" style="border:0"><span><?= e(t('cart.subtotal')) ?></span><span><?= e(money($subtotal)) ?></span></div>
            <div class="cart-summary" style="border:0;color:var(--color-success,#0f9d6f)">
                <span><?= e(t('promo.discount')) ?> (<strong><?= e($promoCode) ?></strong>)</span>
                <span>−<?= e(money($discount)) ?></span>
            </div>
        <?php endif; ?>
        <div class="cart-summary"><span><?= e(t('cart.total')) ?></span><span><?= e(money($total)) ?></span></div>
    </aside>
</div>

<?php if ($stripeConfigured): ?>
    <?php $this->section('scripts'); ?>
        <script src="https://js.stripe.com/v3/"></script>
        <script src="<?= e(asset('js/checkout.js')) ?>" defer></script>
    <?php $this->endSection(); ?>
<?php endif; ?>
