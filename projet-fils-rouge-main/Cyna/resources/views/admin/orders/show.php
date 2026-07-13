<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/admin', ['title' => $title]);
?>

<div class="grid" style="grid-template-columns:2fr 1fr">
    <section class="card">
        <h2>Lignes</h2>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Service</th><th>Période</th><th>Qté</th><th>Total</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e((string) $item['product_name']) ?></td>
                            <td><?= e($item['billing_period'] === 'annual' ? 'Annuel' : 'Mensuel') ?></td>
                            <td><?= (int) $item['quantity'] ?></td>
                            <td><?= e(money((int) $item['line_total_cents'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="cart-summary"><span>Total</span><span><?= e(money((int) $order['total_cents'])) ?></span></div>
    </section>

    <aside>
        <section class="card mb-4">
            <h2>Statut</h2>
            <p><span class="badge badge--muted"><?= e(t('order_status.' . $order['status'])) ?></span></p>
            <form action="<?= e(url('/admin/commandes/' . $order['id'] . '/statut')) ?>" method="post">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="status">Modifier le statut</label>
                    <select id="status" name="status">
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= e($s) ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= e(t('order_status.' . $s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn--primary">Mettre à jour</button>
            </form>
        </section>
        <section class="card">
            <h2>Facturation</h2>
            <p style="margin:0"><?= e((string) $order['billing_name']) ?><br>
                <?= e((string) $order['billing_line1']) ?><br>
                <?= e($order['billing_postal_code'] . ' ' . $order['billing_city']) ?><br>
                <?= e((string) $order['billing_country']) ?></p>
            <p class="text-muted mt-4"><?= e((string) $order['email']) ?></p>
            <?php if ($order['payment_brand']): ?>
                <p class="text-muted"><?= e(ucfirst((string) $order['payment_brand'])) ?> •••• <?= e((string) $order['payment_last4']) ?></p>
            <?php endif; ?>
        </section>
    </aside>
</div>
