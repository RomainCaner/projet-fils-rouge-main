<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
?>

<div class="card" style="max-width:440px;margin-inline:auto">
    <h1><?= e(t('auth.login_title')) ?></h1>
    <form action="<?= e(url('/connexion')) ?>" method="post">
        <?= csrf_field() ?>
        <div class="field <?= error('email') ? 'field--invalid' : '' ?>">
            <label for="email"><?= e(t('auth.email')) ?></label>
            <input id="email" type="email" name="email" value="<?= old('email') ?>" required autocomplete="email">
            <?php if (error('email')): ?><p class="field__error"><?= e(error('email')) ?></p><?php endif; ?>
        </div>
        <div class="field">
            <label for="password"><?= e(t('auth.password')) ?></label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
        </div>
        <div class="field">
            <label style="font-weight:400"><input type="checkbox" name="remember" value="1"> <?= e(t('auth.remember')) ?></label>
        </div>
        <button type="submit" class="btn btn--primary btn--block"><?= e(t('auth.login_submit')) ?></button>
    </form>
    <p class="mt-4"><a href="<?= e(url('/mot-de-passe-oublie')) ?>"><?= e(t('auth.forgot_link')) ?></a></p>
    <p><?= e(t('auth.no_account')) ?> <a href="<?= e(url('/inscription')) ?>"><?= e(t('menu.register')) ?></a></p>
</div>
