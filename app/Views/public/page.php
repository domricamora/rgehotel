<?php /** @var array|null $page */ ?>
<?= partial('partials.page-hero', [
    'pageTitle' => $page['title'] ?? 'Page',
    'heroImage' => $heroImage ?? 'general/aerial',
    'crumbs'    => [($page['title'] ?? 'Page') => null],
]) ?>
<section class="section">
  <div class="container" style="max-width:760px">
    <?php if ($page): ?>
      <div style="white-space:pre-line;font-size:1.05rem;color:var(--slate)"><?= e($page['body']) ?></div>
    <?php else: ?>
      <p>Content coming soon.</p>
    <?php endif; ?>
    <div class="mt-4"><a class="btn btn-primary" href="<?= e(url('/accommodations')) ?>"><?= icon('calendar') ?> Book Your Stay</a></div>
  </div>
</section>
