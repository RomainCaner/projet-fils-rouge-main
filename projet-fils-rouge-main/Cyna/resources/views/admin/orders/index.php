<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/admin', ['title' => $title]);
?>

<form action="<?= e(url('/admin/commandes')) ?>" method="get" class="admin-toolbar">
    <label for="status">Statut :</label>
    <select id="status" name="status">
        <option value="">Tous</option>
        <?php foreach ($statuses as $s): ?>
            <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(t('order_status.' . $s)) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn--ghost">Filtrer</button>
    <a class="btn btn--primary" href="<?= e(url('/admin/commandes/export' . ($status !== '' ? '?status=' . urlencode($status) : ''))) ?>">
        Exporter (CSV)
    </a>
</form>

<div class="table-wrap card">
    <table class="data">
        <thead><tr><th>Facture</th><th>Client</th><th>Date</th><th>Montant</th><th>Statut</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= e((string) $order['invoice_number']) ?></td>
                    <td><?= e((string) $order['email']) ?></td>
                    <td><?= e(date('d/m/Y', strtotime((string) $order['created_at']))) ?></td>
                    <td><?= e(money((int) $order['total_cents'])) ?></td>
                    <td><span class="badge badge--muted"><?= e(t('order_status.' . $order['status'])) ?></span></td>
                    <td><a href="<?= e(url('/admin/commandes/' . $order['id'])) ?>">Détail</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($orders === []): ?><tr><td colspan="6" class="text-muted">Aucune commande.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php $this->insert('partials/pagination', ['paginator' => $paginator]); ?>
