<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
?>

<div class="layout-sidebar">
    <?php $this->insert('partials/account_nav'); ?>
    <div>
        <h1><?= e(t('account.welcome', ['name' => $user->fullName])) ?></h1>

        <section class="card mb-4">
            <h2><?= e(t('account.active_subscriptions')) ?></h2>
            <?php $active = array_filter($subscriptions, static fn ($s) => $s['status'] === 'active'); ?>
            <?php if ($active === []): ?>
                <p class="text-muted"><?= e(t('account.no_subscription')) ?></p>
            <?php else: ?>
                <ul class="specs">
                    <?php foreach ($active as $sub): ?>
                        <li><strong><?= e((string) $sub['product_name']) ?></strong>
                            — <?= e($sub['billing_period'] === 'annual' ? t('product.period_annual') : t('product.period_monthly')) ?>
                            <?php if ($sub['renews_at']): ?><span class="text-muted">(<?= e(t('account.renews_on', ['date' => date('d/m/Y', strtotime((string) $sub['renews_at']))])) ?>)</span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2><?= e(t('account.recent_orders')) ?></h2>
            <?php if ($recentOrders === []): ?>
                <p class="text-muted"><?= e(t('account.no_order')) ?></p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th><?= e(t('account.invoice')) ?></th><th><?= e(t('account.date')) ?></th><th><?= e(t('account.amount')) ?></th><th><?= e(t('account.status')) ?></th></tr></thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><a href="<?= e(url('/compte/commandes/' . $order['id'])) ?>"><?= e((string) $order['invoice_number']) ?></a></td>
                                    <td><?= e(date('d/m/Y', strtotime((string) $order['created_at']))) ?></td>
                                    <td><?= e(money((int) $order['total_cents'])) ?></td>
                                    <td><span class="badge badge--muted"><?= e(t('order_status.' . $order['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
