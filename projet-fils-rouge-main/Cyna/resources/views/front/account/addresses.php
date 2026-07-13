<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);

/** Champs d'une adresse, factorisés pour la création et l'édition. */
$fields = function (array $a = []) {
    $get = static fn (string $k) => e((string) ($a[$k] ?? ''));
    ?>
    <div class="field"><label><?= e(t('address.first_name')) ?><input name="first_name" value="<?= $get('first_name') ?>" required></label></div>
    <div class="field"><label><?= e(t('address.last_name')) ?><input name="last_name" value="<?= $get('last_name') ?>" required></label></div>
    <div class="field"><label><?= e(t('address.line1')) ?><input name="line1" value="<?= $get('line1') ?>" required></label></div>
    <div class="field"><label><?= e(t('address.line2')) ?><input name="line2" value="<?= $get('line2') ?>"></label></div>
    <div class="field"><label><?= e(t('address.city')) ?><input name="city" value="<?= $get('city') ?>" required></label></div>
    <div class="field"><label><?= e(t('address.region')) ?><input name="region" value="<?= $get('region') ?>"></label></div>
    <div class="field"><label><?= e(t('address.postal_code')) ?><input name="postal_code" value="<?= $get('postal_code') ?>" required></label></div>
    <div class="field"><label><?= e(t('address.country')) ?><input name="country" value="<?= $get('country') ?: 'France' ?>" required></label></div>
    <div class="field"><label><?= e(t('address.phone')) ?><input name="phone" value="<?= $get('phone') ?>"></label></div>
    <?php
};
?>

<div class="layout-sidebar">
    <?php $this->insert('partials/account_nav'); ?>
    <div>
        <h1><?= e(t('account.addresses')) ?></h1>

        <?php foreach ($addresses as $address): ?>
            <div class="card mb-4">
                <p style="margin:0">
                    <strong><?= e($address['first_name'] . ' ' . $address['last_name']) ?></strong>
                    <?php if ($address['is_default']): ?><span class="badge badge--success"><?= e(t('address.default')) ?></span><?php endif; ?><br>
                    <?= e((string) $address['line1']) ?><?php if ($address['line2']): ?>, <?= e((string) $address['line2']) ?><?php endif; ?><br>
                    <?= e($address['postal_code'] . ' ' . $address['city']) ?>, <?= e((string) $address['country']) ?>
                </p>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap" class="mt-4">
                    <?php if (!$address['is_default']): ?>
                        <form action="<?= e(url('/compte/adresses/' . $address['id'] . '/defaut')) ?>" method="post"><?= csrf_field() ?><button class="btn btn--ghost"><?= e(t('address.set_default')) ?></button></form>
                    <?php endif; ?>
                    <form action="<?= e(url('/compte/adresses/' . $address['id'] . '/supprimer')) ?>" method="post"><?= csrf_field() ?><button class="btn btn--danger"><?= e(t('account.delete')) ?></button></form>
                </div>
                <details class="mt-4">
                    <summary><?= e(t('account.edit')) ?></summary>
                    <form action="<?= e(url('/compte/adresses/' . $address['id'] . '/modifier')) ?>" method="post" class="mt-4">
                        <?= csrf_field() ?>
                        <?php $fields($address); ?>
                        <button type="submit" class="btn btn--primary"><?= e(t('account.save')) ?></button>
                    </form>
                </details>
            </div>
        <?php endforeach; ?>

        <details class="card">
            <summary><strong><?= e(t('address.add')) ?></strong></summary>
            <form action="<?= e(url('/compte/adresses')) ?>" method="post" class="mt-4">
                <?= csrf_field() ?>
                <?php $fields(); ?>
                <button type="submit" class="btn btn--primary"><?= e(t('address.add')) ?></button>
            </form>
        </details>
    </div>
</div>
