<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);

// Regroupement des commandes par année (du plus récent au plus ancien).
$byYear = [];
foreach ($orders as $order) {
    $byYear[(int) date('Y', strtotime((string) $order['created_at']))][] = $order;
}
?>

<div class="layout-sidebar">
    <?php $this->insert('partials/account_nav'); ?>
    <div>
        <h1><?= e(t('account.orders')) ?></h1>

        <form action="<?= e(url('/compte/commandes')) ?>" method="get" class="card mb-4" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:end">
            <div class="field" style="margin:0">
                <label for="q"><?= e(t('account.search_order')) ?></label>
                <input id="q" type="search" name="q" value="<?= e((string) $q) ?>">
            </div>
            <div class="field" style="margin:0">
                <label for="year"><?= e(t('account.year')) ?></label>
                <select id="year" name="year">
                    <option value=""><?= e(t('account.all_years')) ?></option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn--ghost"><?= e(t('search.apply')) ?></button>
        </form>

        <?php if ($orders === []): ?>
            <p class="text-muted"><?= e(t('account.no_order')) ?></p>
        <?php else: ?>
            <?php foreach ($byYear as $groupYear => $groupOrders): ?>
                <section class="year-group">
                    <h2><?= $groupYear ?></h2>
                    <div class="table-wrap">
                        <table class="data">
                            <thead><tr><th><?= e(t('account.invoice')) ?></th><th><?= e(t('account.date')) ?></th><th><?= e(t('account.amount')) ?></th><th><?= e(t('account.status')) ?></th><th></th></tr></thead>
                            <tbody>
                                <?php foreach ($groupOrders as $order): ?>
                                    <tr>
                                        <td><?= e((string) $order['invoice_number']) ?></td>
                                        <td><?= e(date('d/m/Y', strtotime((string) $order['created_at']))) ?></td>
                                        <td><?= e(money((int) $order['total_cents'])) ?></td>
                                        <td><span class="badge badge--muted"><?= e(t('order_status.' . $order['status'])) ?></span></td>
                                        <td><a href="<?= e(url('/compte/commandes/' . $order['id'])) ?>"><?= e(t('account.detail')) ?></a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
