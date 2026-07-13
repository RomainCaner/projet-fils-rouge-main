<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
?>

<div class="card" style="max-width:480px;margin-inline:auto">
    <h1><?= e(t('auth.register_title')) ?></h1>
    <form action="<?= e(url('/inscription')) ?>" method="post">
        <?= csrf_field() ?>
        <div class="field <?= error('full_name') ? 'field--invalid' : '' ?>">
            <label for="full_name"><?= e(t('auth.full_name')) ?></label>
            <input id="full_name" name="full_name" value="<?= old('full_name') ?>" required autocomplete="name">
            <?php if (error('full_name')): ?><p class="field__error"><?= e(error('full_name')) ?></p><?php endif; ?>
        </div>
        <div class="field <?= error('email') ? 'field--invalid' : '' ?>">
            <label for="email"><?= e(t('auth.email')) ?></label>
            <input id="email" type="email" name="email" value="<?= old('email') ?>" required autocomplete="email">
            <?php if (error('email')): ?><p class="field__error"><?= e(error('email')) ?></p><?php endif; ?>
        </div>
        <div class="field <?= error('password') ? 'field--invalid' : '' ?>">
            <label for="password"><?= e(t('auth.password')) ?></label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            <p class="field__error" id="pw-help" style="color:var(--color-text-muted)"><?= e(t('auth.password_rule')) ?></p>
            <?php if (error('password')): ?><p class="field__error"><?= e(error('password')) ?></p><?php endif; ?>
        </div>
        <div class="field">
            <label for="password_confirmation"><?= e(t('auth.password_confirm')) ?></label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn--primary btn--block"><?= e(t('auth.register_submit')) ?></button>
    </form>
    <p class="mt-4"><?= e(t('auth.have_account')) ?> <a href="<?= e(url('/connexion')) ?>"><?= e(t('menu.login')) ?></a></p>
</div>
