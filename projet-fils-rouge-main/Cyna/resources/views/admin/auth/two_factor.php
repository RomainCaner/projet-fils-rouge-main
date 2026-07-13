<?php /** Étape 2FA : enrôlement (1re fois) ou saisie du code TOTP. */ ?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — Cyna Admin</title>
    <link rel="stylesheet" href="<?= e(asset('css/design-system.css')) ?>">
</head>
<body>
<?php $this->insert('partials/flash'); ?>
<main class="container" style="flex:1;display:flex;align-items:center;justify-content:center;padding-block:4rem">
    <div class="card" style="width:100%;max-width:440px">
        <h1 style="color:var(--color-primary)">Double authentification</h1>

        <?php if ($enrolling): ?>
            <p>Pour sécuriser votre accès, configurez une application d'authentification
                (Google Authenticator, Authy...) en saisissant la clé ci-dessous, puis entrez le code généré.</p>
            <p>Clé secrète :</p>
            <p style="font-family:monospace;font-size:1.1rem;background:var(--color-surface-2);padding:.75rem;border-radius:6px;word-break:break-all"><?= e($secret) ?></p>
            <details class="mb-4"><summary>URI otpauth (saisie automatique)</summary>
                <p style="font-family:monospace;font-size:.8rem;word-break:break-all"><?= e($uri) ?></p>
            </details>
        <?php else: ?>
            <p>Saisissez le code à 6 chiffres généré par votre application d'authentification.</p>
        <?php endif; ?>

        <form action="<?= e(url('/admin/2fa')) ?>" method="post">
            <?= csrf_field() ?>
            <div class="field <?= error('code') ? 'field--invalid' : '' ?>">
                <label for="code">Code de vérification</label>
                <input id="code" name="code" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" required autofocus>
                <?php if (error('code')): ?><p class="field__error"><?= e(error('code')) ?></p><?php endif; ?>
            </div>
            <button type="submit" class="btn btn--primary btn--block">Valider</button>
        </form>
    </div>
</main>
</body>
</html>
