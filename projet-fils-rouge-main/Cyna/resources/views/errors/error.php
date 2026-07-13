<?php /** Page d'erreur générique (404, 403, 419, 500...). Autonome. */ ?>
<!DOCTYPE html>
<html lang="<?= e(locale()) ?>" dir="<?= e(dir_attr()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= (int) $status ?> — Cyna</title>
    <link rel="stylesheet" href="<?= e(asset('css/design-system.css')) ?>">
</head>
<body>
<main class="container" style="flex:1;display:flex;align-items:center;justify-content:center;text-align:center;padding-block:4rem">
    <div class="card">
        <h1 style="font-size:3rem;color:var(--color-primary)"><?= (int) $status ?></h1>
        <p><?= e($message) ?></p>
        <a class="btn btn--primary" href="<?= e(url('/')) ?>"><?= e(t('app.back_home')) ?></a>
    </div>
</main>
</body>
</html>
