<?php
/** @var \Cyna\Core\View $this */
$this->extends('layouts/admin', ['title' => $title]);
?>

<div class="card" style="max-width:720px">
    <p class="text-muted"><?= e(date('d/m/Y H:i', strtotime((string) $message['created_at']))) ?></p>
    <h2><?= e((string) $message['subject']) ?></h2>
    <p><strong>De :</strong> <a href="mailto:<?= e((string) $message['email']) ?>"><?= e((string) $message['email']) ?></a></p>
    <hr style="border-color:var(--color-border)">
    <p style="white-space:pre-wrap"><?= e((string) $message['body']) ?></p>

    <form action="<?= e(url('/admin/messages/' . $message['id'] . '/statut')) ?>" method="post" class="mt-4">
        <?= csrf_field() ?>
        <div class="field" style="max-width:240px">
            <label for="status">Statut</label>
            <select id="status" name="status">
                <?php foreach (['new' => 'Nouveau', 'read' => 'Lu', 'archived' => 'Archivé'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $message['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn--primary">Mettre à jour</button>
        <a class="btn btn--ghost" href="<?= e(url('/admin/messages')) ?>">Retour</a>
    </form>
</div>
