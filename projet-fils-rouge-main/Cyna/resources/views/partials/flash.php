<?php /** Messages flash (succès / erreur / info) affichés une seule fois. */ ?>
<?php $messages = flash_messages(); ?>
<?php if ($messages !== []): ?>
    <div class="container flash-stack" role="status" aria-live="polite">
        <?php foreach ($messages as $type => $items): ?>
            <?php foreach ($items as $message): ?>
                <div class="flash flash--<?= e($type) ?>"><?= e($message) ?></div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
