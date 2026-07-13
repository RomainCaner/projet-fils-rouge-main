<?php
/**
 * Récapitulatif financier d'une commande : sous-total, remise, HT, TVA, TTC.
 * Les prix étant TTC, la TVA (20 %) est calculée à rebours.
 *
 * @var \Cyna\Core\View $this
 * @var array<string,mixed> $order
 */
$ttc  = (int) $order['total_cents'];
$disc = (int) ($order['discount_cents'] ?? 0);
$ht   = (int) round($ttc / 1.20);
$vat  = $ttc - $ht;
?>
<?php if ($disc > 0): ?>
    <div class="cart-summary" style="border:0">
        <span><?= e(t('cart.subtotal')) ?></span><span><?= e(money($ttc + $disc)) ?></span>
    </div>
    <div class="cart-summary" style="border:0;color:var(--color-success,#0f9d6f)">
        <span><?= e(t('promo.discount')) ?><?= ($order['discount_code'] ?? null) !== null ? ' (' . e((string) $order['discount_code']) . ')' : '' ?></span>
        <span>&minus;<?= e(money($disc)) ?></span>
    </div>
<?php endif; ?>
<div class="cart-summary" style="border:0"><span><?= e(t('invoice.total_ht')) ?></span><span><?= e(money($ht)) ?></span></div>
<div class="cart-summary" style="border:0"><span><?= e(t('invoice.vat')) ?> (20&nbsp;%)</span><span><?= e(money($vat)) ?></span></div>
<div class="cart-summary"><span><?= e(t('invoice.total_ttc')) ?></span><span><?= e(money($ttc)) ?></span></div>
