<?php foreach (['success' => 'success', 'error' => 'error', 'info' => 'info'] as $key => $type):
    $msg = flash($key);
    if ($msg): ?>
    <div class="alert alert-<?= $type ?>"><?= e($msg) ?></div>
<?php endif; endforeach; ?>
