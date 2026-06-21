<?php /** @var array $rooms */ ?>
<?= partial('partials.page-hero', [
    'pageTitle' => 'Accommodations',
    'pageSub'   => 'Bright, modern rooms steps from the white-sand shore — from cosy doubles to spacious family and group rooms.',
    'heroImage' => 'general/kalanggaman',
    'crumbs'    => ['Accommodations' => null],
]) ?>

<section class="section">
  <div class="container">
    <?= partial('partials.flash') ?>
    <?php if (!empty($check_in) && !empty($check_out)): ?>
      <div class="alert alert-info"><?= icon('calendar','',16) ?> Showing availability for <strong><?= e(date('M j, Y', strtotime($check_in))) ?></strong> – <strong><?= e(date('M j, Y', strtotime($check_out))) ?></strong>.</div>
    <?php endif; ?>

    <div class="mb-3"><?= partial('partials.book-bar', ['check_in' => $check_in ?? '', 'check_out' => $check_out ?? '']) ?></div>

    <div class="grid grid-3 mt-4">
      <?php foreach ($rooms as $room) echo partial('partials.room-card', ['room' => $room]); ?>
    </div>
  </div>
</section>
