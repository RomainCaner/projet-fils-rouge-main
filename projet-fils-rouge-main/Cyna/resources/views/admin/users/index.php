<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/admin', ['title' => $title]);
?>

<form action="<?= e(url('/admin/utilisateurs')) ?>" method="get" class="admin-toolbar">
    <label class="visually-hidden" for="q">Rechercher</label>
    <input id="q" type="search" name="q" value="<?= e((string) $q) ?>" placeholder="Nom ou e-mail...">
    <button class="btn btn--ghost">Rechercher</button>
</form>

<div class="table-wrap card">
    <table class="data">
        <thead><tr><th>Nom</th><th>E-mail</th><th>Rôle</th><th>E-mail vérifié</th><th>Inscrit le</th></tr></thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= e((string) $user['full_name']) ?></td>
                    <td><?= e((string) $user['email']) ?></td>
                    <td><span class="badge <?= $user['role'] === 'admin' ? 'badge--warning' : 'badge--muted' ?>"><?= e((string) $user['role']) ?></span></td>
                    <td><?= $user['email_verified_at'] ? '✓' : '—' ?></td>
                    <td><?= e(date('d/m/Y', strtotime((string) $user['created_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($users === []): ?><tr><td colspan="5" class="text-muted">Aucun utilisateur.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php $this->insert('partials/pagination', ['paginator' => $paginator]); ?>
