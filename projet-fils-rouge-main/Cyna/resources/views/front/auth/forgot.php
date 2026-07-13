<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
?>

<div class="card" style="max-width:440px;margin-inline:auto">
    <h1><?= e(t('auth.forgot_title')) ?></h1>
    <p class="text-muted"><?= e(t('auth.forgot_intro')) ?></p>
    <form action="<?= e(url('/mot-de-passe-oublie')) ?>" method="post">
        <?= csrf_field() ?>
        <div class="field <?= error('email') ? 'field--invalid' : '' ?>">
            <label for="email"><?= e(t('auth.email')) ?></label>
            <input id="email" type="email" name="email" value="<?= old('email') ?>" required autocomplete="email">
            <?php if (error('email')): ?><p class="field__error"><?= e(error('email')) ?></p><?php endif; ?>
        </div>
        <button type="submit" class="btn btn--primary btn--block"><?= e(t('auth.forgot_submit')) ?></button>
    </form>
</div>
