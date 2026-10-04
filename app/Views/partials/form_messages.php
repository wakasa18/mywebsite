<?php
$flashErrors = session()->getFlashdata('errors');
$flashError = session()->getFlashdata('error');
?>
<?php if (!empty($flashErrors) && is_array($flashErrors)): ?>
    <div class="alert danger" role="alert">
        <strong>Please check the following:</strong>
        <ul style="margin:8px 0 0;padding-left:20px;">
            <?php foreach ($flashErrors as $message): ?>
                <li><?= esc($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert danger" role="alert"><?= esc($flashError) ?></div>
<?php endif; ?>
