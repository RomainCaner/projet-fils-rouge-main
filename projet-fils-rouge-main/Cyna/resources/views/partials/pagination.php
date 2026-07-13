<?php
/**
 * Pagination réutilisable.
 * @var \Cyna\Core\Paginator $paginator
 */
if ($paginator->lastPage <= 1) {
    return;
}
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$query = $_GET;
unset($query['page']);
?>
<nav class="pagination" aria-label="<?= e(t('app.pagination')) ?>">
    <?php if ($paginator->hasPrevious()): ?>
        <a href="<?= e($paginator->url($path, $paginator->page - 1, $query)) ?>" rel="prev">‹ <?= e(t('app.previous')) ?></a>
    <?php endif; ?>

    <span class="pagination__status"><?= e(t('app.page')) ?> <?= $paginator->page ?> / <?= $paginator->lastPage ?></span>

    <?php if ($paginator->hasNext()): ?>
        <a href="<?= e($paginator->url($path, $paginator->page + 1, $query)) ?>" rel="next"><?= e(t('app.next')) ?> ›</a>
    <?php endif; ?>
</nav>
