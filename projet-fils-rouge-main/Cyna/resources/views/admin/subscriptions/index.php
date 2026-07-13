<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/admin', ['title' => $title]);
$badges = ['active' => 'badge--success', 'cancelled' => 'badge--danger', 'expired' => 'badge--muted'];
?>

<form action="<?= e(url('/admin/abonnements')) ?>" method="get" class="admin-toolbar">
    <label for="status">Statut :</label>
    <select id="status" name="status">
        <option value="">Tous</option>
        <?php foreach ($statuses as $s): ?>
            <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(t('subscription_status.' . $s)) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn--ghost">Filtrer</button>
</form>

<div class="table-wrap card">
    <table class="data">
        <thead><tr><th>Client</th><th>Service</th><th>Périodicité</th><th>Qté</th><th>Échéance</th><th>Renouv. auto</th><th>Statut</th></tr></thead>
        <tbody>
            <?php foreach ($subscriptions as $sub): ?>
                <tr>
                    <td><?= e((string) ($sub['user_email'] ?? '—')) ?></td>
                    <td><?= e((string) $sub['product_name']) ?></td>
                    <td><?= e($sub['billing_period'] === 'annual' ? 'Annuel' : 'Mensuel') ?></td>
                    <td><?= (int) $sub['quantity'] ?></td>
                    <td><?= $sub['renews_at'] ? e(date('d/m/Y', strtotime((string) $sub['renews_at']))) : '—' ?></td>
                    <td>
                        <?php if ((int) $sub['auto_renew'] === 1): ?>
                            <span class="badge badge--success">Oui</span>
                        <?php else: ?>
                            <span class="badge badge--muted">Non</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $badges[$sub['status']] ?? 'badge--muted' ?>"><?= e(t('subscription_status.' . $sub['status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($subscriptions === []): ?><tr><td colspan="7" class="text-muted">Aucun abonnement.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php $this->insert('partials/pagination', ['paginator' => $paginator]); ?>
