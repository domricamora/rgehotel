<section class="section center" style="min-height:60vh;display:grid;place-items:center">
  <div class="container" style="max-width:560px">
    <span class="eyebrow">Error 404</span>
    <h1 style="margin-top:8px">Lost at sea</h1>
    <p class="mt-2"><?= e($message ?? "We couldn't find the page you were looking for.") ?></p>
    <div class="mt-4 flex items-center gap-2 wrap" style="justify-content:center">
      <a class="btn btn-primary" href="<?= e(url('/')) ?>"><?= icon('arrow-right') ?> Back to Home</a>
      <a class="btn btn-outline" href="<?= e(url('/accommodations')) ?>">View Rooms</a>
    </div>
  </div>
</section>
