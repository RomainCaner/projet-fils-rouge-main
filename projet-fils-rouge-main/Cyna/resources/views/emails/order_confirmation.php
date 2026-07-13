<?php
/**
 * E-mail de confirmation de commande (récapitulatif + lien de téléchargement
 * de la facture). Sans pièce jointe, pour une délivrabilité fiable.
 *
 * @var array<string,mixed>       $order
 * @var list<array<string,mixed>> $items
 * @var string                    $invoiceUrl
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;background:#f4f6fb;padding:24px;color:#1a2233">
    <div style="max-width:560px;margin:auto;background:#fff;border-radius:10px;padding:32px">
        <h1 style="color:#00b37a;margin-top:0">Cyna</h1>
        <h2><?= e(t('checkout.thank_you')) ?></h2>
        <p><?= e(t('email.order_intro')) ?></p>
        <p style="color:#6b7280;font-size:14px">
            <?= e(t('email.order_invoice_label')) ?> :
            <strong><?= e((string) $order['invoice_number']) ?></strong>
        </p>

        <h3 style="margin-top:28px"><?= e(t('email.order_summary')) ?></h3>
        <table style="width:100%;border-collapse:collapse;font-size:14px">
            <thead>
                <tr style="text-align:left;border-bottom:2px solid #e5e7eb">
                    <th style="padding:8px 4px"><?= e(t('product.title')) ?></th>
                    <th style="padding:8px 4px"><?= e(t('product.billing_period')) ?></th>
                    <th style="padding:8px 4px"><?= e(t('product.quantity')) ?></th>
                    <th style="padding:8px 4px;text-align:right"><?= e(t('cart.total')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr style="border-bottom:1px solid #f0f2f5">
                        <td style="padding:8px 4px"><?= e((string) $item['product_name']) ?></td>
                        <td style="padding:8px 4px"><?= e($item['billing_period'] === 'annual' ? t('product.period_annual') : t('product.period_monthly')) ?></td>
                        <td style="padding:8px 4px"><?= (int) $item['quantity'] ?></td>
                        <td style="padding:8px 4px;text-align:right"><?= e(money((int) $item['line_total_cents'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php $discount = (int) ($order['discount_cents'] ?? 0); ?>
        <?php if ($discount > 0): ?>
            <p style="text-align:right;margin:12px 0 0;color:#6b7280">
                <?= e(t('promo.discount')) ?><?= ($order['discount_code'] ?? null) !== null ? ' (' . e((string) $order['discount_code']) . ')' : '' ?> :
                −<?= e(money($discount)) ?>
            </p>
        <?php endif; ?>

        <p style="text-align:right;font-size:16px;margin:12px 0 0">
            <strong><?= e(t('email.order_total')) ?> : <?= e(money((int) $order['total_cents'])) ?></strong>
        </p>

        <?php if (!empty($invoiceUrl)): ?>
            <p style="margin-top:28px;text-align:center">
                <a href="<?= e((string) $invoiceUrl) ?>" style="display:inline-block;background:#00b37a;color:#fff;text-decoration:none;padding:12px 26px;border-radius:8px;font-weight:bold"><?= e(t('account.download_invoice')) ?></a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
