<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
?>

<h1><?= e(t('contact.title')) ?></h1>
<p class="text-muted"><?= e(t('contact.intro')) ?></p>

<form class="card" action="<?= e(url('/contact')) ?>" method="post" style="max-width:640px">
    <?= csrf_field() ?>
    <div class="field <?= error('email') ? 'field--invalid' : '' ?>">
        <label for="email"><?= e(t('contact.email')) ?></label>
        <input id="email" type="email" name="email" value="<?= old('email') ?>" required>
        <?php if (error('email')): ?><p class="field__error"><?= e(error('email')) ?></p><?php endif; ?>
    </div>
    <div class="field <?= error('subject') ? 'field--invalid' : '' ?>">
        <label for="subject"><?= e(t('contact.subject')) ?></label>
        <input id="subject" name="subject" value="<?= old('subject') ?>" required>
        <?php if (error('subject')): ?><p class="field__error"><?= e(error('subject')) ?></p><?php endif; ?>
    </div>
    <div class="field <?= error('message') ? 'field--invalid' : '' ?>">
        <label for="message"><?= e(t('contact.message')) ?></label>
        <textarea id="message" name="message" required><?= old('message') ?></textarea>
        <?php if (error('message')): ?><p class="field__error"><?= e(error('message')) ?></p><?php endif; ?>
    </div>
    <button type="submit" class="btn btn--primary"><?= e(t('contact.send')) ?></button>
</form>
