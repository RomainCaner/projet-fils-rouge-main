<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/admin', ['title' => $title]);
?>

<div class="admin-toolbar">
    <span class="text-muted">Les codes actifs et non expirés sont utilisables au panier.</span>
</div>

<div class="card" style="margin-bottom:1.5rem">
    <h2 style="margin-top:0">Nouveau code</h2>
    <form action="<?= e(url('/admin/promotions')) ?>" method="post"
          style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;align-items:end">
        <?= csrf_field() ?>
        <div>
            <label for="code">Code</label>
            <input id="code" type="text" name="code" value="<?= old('code') ?>" required maxlength="40" placeholder="EX : CYNA25">
            <?php if ($e = error('code')): ?><small style="color:var(--color-danger)"><?= e($e) ?></small><?php endif; ?>
        </div>
        <div>
            <label for="title">Titre</label>
            <input id="title" type="text" name="title" value="<?= old('title') ?>" maxlength="120" placeholder="Ex : Offre de bienvenue">
        </div>
        <div>
            <label for="type">Type</label>
            <select id="type" name="type">
                <option value="percent">Pourcentage (%)</option>
                <option value="fixed">Montant fixe (€)</option>
            </select>
        </div>
        <div>
            <label for="value">Valeur</label>
            <input id="value" type="number" name="value" value="<?= old('value') ?>" min="0" step="0.01" required
                   placeholder="10 (% ou €)">
            <?php if ($e = error('value')): ?><small style="color:var(--color-danger)"><?= e($e) ?></small><?php endif; ?>
        </div>
        <div>
            <label for="max_uses">Utilisations max</label>
            <input id="max_uses" type="number" name="max_uses" value="<?= old('max_uses') ?>" min="1" placeholder="illimité">
        </div>
        <div>
            <label for="expires_at">Expire le</label>
            <input id="expires_at" type="date" name="expires_at" value="<?= old('expires_at') ?>">
        </div>
        <div>
            <button type="submit" class="btn btn--primary btn--block">Créer</button>
        </div>
    </form>
</div>

<div class="table-wrap card">
    <table class="data">
        <thead><tr><th>Code</th><th>Titre</th><th>Type</th><th>Valeur</th><th>Utilisations</th><th>Expire</th><th>Statut</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($promotions as $promo): ?>
                <tr>
                    <td><code><?= e((string) $promo['code']) ?></code></td>
                    <td><?= ($promo['title'] ?? null) !== null ? e((string) $promo['title']) : '<span class="text-muted">—</span>' ?></td>
                    <td><?= $promo['type'] === 'fixed' ? 'Montant fixe' : 'Pourcentage' ?></td>
                    <td><?= $promo['type'] === 'fixed' ? e(money((int) $promo['value'])) : (int) $promo['value'] . ' %' ?></td>
                    <td>
                        <?= (int) $promo['uses'] ?><?= $promo['max_uses'] !== null ? ' / ' . (int) $promo['max_uses'] : '' ?>
                    </td>
                    <td><?= $promo['expires_at'] !== null ? e(substr((string) $promo['expires_at'], 0, 10)) : '—' ?></td>
                    <td>
                        <?php if ((int) $promo['active'] === 1): ?>
                            <span class="badge badge--success">Actif</span>
                        <?php else: ?>
                            <span class="badge badge--danger">Inactif</span>
                        <?php endif; ?>
                    </td>
                    <td style="display:flex;gap:.5rem">
                        <form action="<?= e(url('/admin/promotions/' . $promo['id'] . '/statut')) ?>" method="post">
                            <?= csrf_field() ?><button class="link-button"><?= (int) $promo['active'] === 1 ? 'Désactiver' : 'Activer' ?></button>
                        </form>
                        <form action="<?= e(url('/admin/promotions/' . $promo['id'] . '/supprimer')) ?>" method="post" data-confirm="Supprimer ce code de réduction ?">
                            <?= csrf_field() ?><button class="link-button" style="color:var(--color-danger)">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($promotions === []): ?><tr><td colspan="8" class="text-muted">Aucun code de réduction.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php $this->section('scripts'); ?>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
<?php $this->endSection(); ?>
