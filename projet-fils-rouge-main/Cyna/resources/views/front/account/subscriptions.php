<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/front', ['title' => $title]);
$labels = ['active' => 'badge--success', 'cancelled' => 'badge--danger', 'expired' => 'badge--muted'];
?>

<div class="layout-sidebar">
    <?php $this->insert('partials/account_nav'); ?>
    <div>
        <h1><?= e(t('account.subscriptions')) ?></h1>

        <?php if ($subscriptions === []): ?>
            <p class="text-muted"><?= e(t('account.no_subscription')) ?></p>
        <?php else: ?>
            <?php foreach ($subscriptions as $sub):
                $active  = $sub['status'] === 'active';
                $auto    = (int) $sub['auto_renew'] === 1;
                $endDate = $sub['renews_at'] ? date('d/m/Y', strtotime((string) $sub['renews_at'])) : null;
            ?>
                <div class="card mb-4">
                    <h2 style="margin:0"><?= e((string) $sub['product_name']) ?>
                        <span class="badge <?= $labels[$sub['status']] ?? 'badge--muted' ?>"><?= e(t('subscription_status.' . $sub['status'])) ?></span>
                        <?php if ($active && !$auto): ?><span class="badge badge--warning"><?= e(t('account.cancelled_flag')) ?></span><?php endif; ?>
                    </h2>
                    <p class="text-muted">
                        <?= e($sub['billing_period'] === 'annual' ? t('product.period_annual') : t('product.period_monthly')) ?>
                        — <?= e(t('product.quantity')) ?> : <?= (int) $sub['quantity'] ?>
                        <?php if ($endDate && $active): ?>
                            <br>
                            <?php if ($auto): ?>
                                <?= e(t('account.renews_on', ['date' => $endDate])) ?>
                            <?php else: ?>
                                <strong><?= e(t('account.active_until', ['date' => $endDate])) ?></strong>
                            <?php endif; ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($active): ?>
                        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                            <?php if ($auto): ?>
                                <form action="<?= e(url('/compte/abonnements/' . $sub['id'] . '/renouveler')) ?>" method="post"><?= csrf_field() ?><button class="btn btn--primary"><?= e(t('account.renew')) ?></button></form>
                                <form action="<?= e(url('/compte/abonnements/' . $sub['id'] . '/resilier')) ?>" method="post"><?= csrf_field() ?><button class="btn btn--danger"><?= e(t('account.cancel')) ?></button></form>
                            <?php else: ?>
                                <form action="<?= e(url('/compte/abonnements/' . $sub['id'] . '/reactiver')) ?>" method="post"><?= csrf_field() ?><button class="btn btn--primary"><?= e(t('account.reactivate')) ?></button></form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
