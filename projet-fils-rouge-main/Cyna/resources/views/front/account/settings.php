<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
?>

<div class="layout-sidebar">
    <?php $this->insert('partials/account_nav'); ?>
    <div>
        <h1><?= e(t('account.settings')) ?></h1>

        <form class="card mb-4" action="<?= e(url('/compte/parametres/profil')) ?>" method="post">
            <?= csrf_field() ?>
            <h2><?= e(t('account.personal_info')) ?></h2>
            <div class="field"><label for="full_name"><?= e(t('auth.full_name')) ?></label><input id="full_name" name="full_name" value="<?= old('full_name', $user->fullName) ?>" required></div>
            <div class="field <?= error('email') ? 'field--invalid' : '' ?>">
                <label for="email"><?= e(t('auth.email')) ?></label>
                <input id="email" type="email" name="email" value="<?= old('email', $user->email) ?>" required>
                <?php if (error('email')): ?><p class="field__error"><?= e(error('email')) ?></p><?php endif; ?>
            </div>
            <div class="field <?= error('current_password') ? 'field--invalid' : '' ?>">
                <label for="current_password"><?= e(t('account.current_password_email')) ?></label>
                <input id="current_password" type="password" name="current_password" autocomplete="current-password">
                <?php if (error('current_password')): ?><p class="field__error"><?= e(error('current_password')) ?></p><?php endif; ?>
            </div>
            <button type="submit" class="btn btn--primary"><?= e(t('account.save')) ?></button>
        </form>

        <form class="card" action="<?= e(url('/compte/parametres/mot-de-passe')) ?>" method="post">
            <?= csrf_field() ?>
            <h2><?= e(t('account.change_password')) ?></h2>
            <div class="field <?= error('current_password') ? 'field--invalid' : '' ?>">
                <label for="cur_pw"><?= e(t('account.current_password')) ?></label>
                <input id="cur_pw" type="password" name="current_password" required autocomplete="current-password">
                <?php if (error('current_password')): ?><p class="field__error"><?= e(error('current_password')) ?></p><?php endif; ?>
            </div>
            <div class="field <?= error('password') ? 'field--invalid' : '' ?>">
                <label for="password"><?= e(t('auth.new_password')) ?></label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
                <?php if (error('password')): ?><p class="field__error"><?= e(error('password')) ?></p><?php endif; ?>
            </div>
            <div class="field"><label for="password_confirmation"><?= e(t('auth.password_confirm')) ?></label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"></div>
            <button type="submit" class="btn btn--primary"><?= e(t('account.update_password')) ?></button>
        </form>
    </div>
</div>
