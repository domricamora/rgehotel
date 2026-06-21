<section class="section center" style="min-height:50vh;display:grid;place-items:center">
  <div class="container" style="max-width:560px">
    <span class="eyebrow">Error 403</span>
    <h1 style="margin-top:8px">Access denied</h1>
    <p class="mt-2"><?= e($message ?? 'You do not have permission to view this page.') ?></p>
    <div class="mt-4"><a class="btn btn-primary" href="<?= e(url('/admin')) ?>">Back to Dashboard</a></div>
  </div>
</section>
