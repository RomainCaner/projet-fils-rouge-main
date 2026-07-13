<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
?>

<div class="card" style="max-width:440px;margin-inline:auto">
    <h1><?= e(t('auth.reset_title')) ?></h1>
    <form action="<?= e(url('/reinitialiser')) ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="field <?= error('password') ? 'field--invalid' : '' ?>">
            <label for="password"><?= e(t('auth.new_password')) ?></label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            <p class="field__error" style="color:var(--color-text-muted)"><?= e(t('auth.password_rule')) ?></p>
            <?php if (error('password')): ?><p class="field__error"><?= e(error('password')) ?></p><?php endif; ?>
        </div>
        <div class="field">
            <label for="password_confirmation"><?= e(t('auth.password_confirm')) ?></label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn--primary btn--block"><?= e(t('auth.reset_submit')) ?></button>
    </form>
</div>
