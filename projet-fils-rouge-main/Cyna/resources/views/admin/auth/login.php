<?php /** Connexion administrateur (page autonome, sans barre latérale). */ ?>
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
    <div class="card" style="width:100%;max-width:400px">
        <h1 style="color:var(--color-primary)">Cyna Admin</h1>
        <p class="text-muted">Espace réservé aux administrateurs.</p>
        <form action="<?= e(url('/admin/connexion')) ?>" method="post">
            <?= csrf_field() ?>
            <div class="field <?= error('email') ? 'field--invalid' : '' ?>">
                <label for="email">E-mail</label>
                <input id="email" type="email" name="email" value="<?= old('email') ?>" required autocomplete="username">
                <?php if (error('email')): ?><p class="field__error"><?= e(error('email')) ?></p><?php endif; ?>
            </div>
            <div class="field">
                <label for="password">Mot de passe</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn--primary btn--block">Se connecter</button>
        </form>
    </div>
</main>
</body>
</html>
