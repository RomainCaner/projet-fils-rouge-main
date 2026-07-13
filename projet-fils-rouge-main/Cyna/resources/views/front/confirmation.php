<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
?>

<div class="card text-center">
    <h1><?= e(t('checkout.thank_you')) ?></h1>
    <p><?= e(t('checkout.order_confirmed', ['invoice' => $order['invoice_number']])) ?></p>
    <p class="text-muted"><?= e(t('checkout.email_sent')) ?></p>
</div>

<section class="card mt-4">
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
    <a class="btn btn--ghost" href="<?= e(url('/')) ?>"><?= e(t('checkout.back_home')) ?></a>
</section>
