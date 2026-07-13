<?php /** E-mail de réinitialisation de mot de passe. @var string $link */ ?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;background:#f4f6fb;padding:24px;color:#1a2233">
    <div style="max-width:520px;margin:auto;background:#fff;border-radius:10px;padding:32px">
        <h1 style="color:#00b37a">Cyna</h1>
        <h2><?= e(t('auth.reset_subject')) ?></h2>
        <p><?= e(t('email.reset_body')) ?></p>
        <p style="text-align:center;margin:28px 0">
            <a href="<?= e($link) ?>" style="background:#14b8a6;color:#05221f;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold"><?= e(t('email.reset_button')) ?></a>
        </p>
        <p style="color:#6b7280;font-size:13px"><?= e(t('email.link_validity')) ?></p>
        <p style="color:#6b7280;font-size:12px;word-break:break-all"><?= e($link) ?></p>
    </div>
</body>
</html>
