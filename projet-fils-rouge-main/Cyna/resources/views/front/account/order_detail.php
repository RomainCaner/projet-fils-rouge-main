<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
?>

<div class="layout-sidebar">
    <?php $this->insert('partials/account_nav'); ?>
    <div>
        <h1><?= e(t('account.order')) ?> <?= e((string) $order['invoice_number']) ?></h1>
        <p class="text-muted"><?= e(date('d/m/Y', strtotime((string) $order['created_at']))) ?>
            — <span class="badge badge--muted"><?= e(t('order_status.' . $order['status'])) ?></span></p>

        <section class="card mb-4">
            <h2><?= e(t('checkout.summary')) ?></h2>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th><?= e(t('product.title')) ?></th><th><?= e(t('product.billing_period')) ?></th><th><?= e(t('product.quantity')) ?></th><th><?= e(t('cart.total')) ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= e((string) $item['product_name']) ?></td>
                                <td><?= e($item['billing_period'] === 'annual' ? t('product.period_annual') : t('product.period_monthly')) ?></td>
                                <td><?= (int) $item['quantity'] ?></td>
                                <td><?= e(money((int) $item['line_total_cents'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php $this->insert('partials/order_totals', ['order' => $order]); ?>
        </section>

        <div class="grid" style="grid-template-columns:1fr 1fr">
            <section class="card">
                <h2><?= e(t('checkout.billing_address')) ?></h2>
                <p style="margin:0"><?= e((string) $order['billing_name']) ?><br>
                    <?= e((string) $order['billing_line1']) ?><br>
                    <?= e($order['billing_postal_code'] . ' ' . $order['billing_city']) ?><br>
                    <?= e((string) $order['billing_country']) ?></p>
            </section>
            <section class="card">
                <h2><?= e(t('account.payment')) ?></h2>
                <p style="margin:0">
                    <?php if ($order['payment_brand']): ?><?= e(ucfirst((string) $order['payment_brand'])) ?> •••• <?= e((string) $order['payment_last4']) ?><?php else: ?>—<?php endif; ?>
                </p>
                <a class="btn btn--primary mt-4" href="<?= e(url('/compte/commandes/' . $order['id'] . '/facture')) ?>"><?= e(t('account.download_invoice')) ?></a>
            </section>
        </div>
    </div>
</div>
