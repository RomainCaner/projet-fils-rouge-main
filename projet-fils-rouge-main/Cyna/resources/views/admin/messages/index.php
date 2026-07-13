<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/admin', ['title' => $title]);
$labels = ['new' => 'badge--success', 'read' => 'badge--muted', 'archived' => 'badge--warning'];
?>

<div class="table-wrap card">
    <table class="data">
        <thead><tr><th>Date</th><th>E-mail</th><th>Sujet</th><th>Statut</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($messages as $message): ?>
                <tr>
                    <td><?= e(date('d/m/Y H:i', strtotime((string) $message['created_at']))) ?></td>
                    <td><?= e((string) $message['email']) ?></td>
                    <td><?= e((string) $message['subject']) ?></td>
                    <td><span class="badge <?= $labels[$message['status']] ?? 'badge--muted' ?>"><?= e((string) $message['status']) ?></span></td>
                    <td><a href="<?= e(url('/admin/messages/' . $message['id'])) ?>">Ouvrir</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($messages === []): ?><tr><td colspan="5" class="text-muted">Aucun message.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php $this->insert('partials/pagination', ['paginator' => $paginator]); ?>
