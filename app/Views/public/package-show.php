<?php /** @var array $package @var array $roomTypes @var array $services */ ?>
<?= partial('partials.page-hero', [
    'pageTitle' => $package['name'],
    'pageSub'   => $package['summary'],
    'heroImage' => $package['image'] ?? 'general/sunset',
    'crumbs'    => ['Packages' => url('/packages'), $package['name'] => null],
]) ?>
<section class="section">
  <div class="container">
    <div class="detail-layout">
      <div>
        <h2 style="font-size:1.5rem">What's included</h2>
        <p class="mt-2" style="white-space:pre-line"><?= e($package['description']) ?></p>
        <?php if ($package['inclusions']): ?>
          <ul class="amenity-list mt-3">
            <?php foreach (preg_split('/\r?\n/', $package['inclusions']) as $h): if(trim($h)==='')continue; ?>
              <li><?= icon('check') ?> <?= e(trim($h)) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <?php if ($services): ?>
        <h3 class="mt-4">Experiences included</h3>
        <div class="grid grid-2 mt-2">
          <?php foreach ($services as $s): ?>
          <article class="card"><a class="card__media" href="<?= e(url('/services/'.$s['slug'])) ?>"><?= img_tag($s['image'] ?? null, $s['name']) ?></a>
            <div class="card__body"><h3 style="font-size:1.05rem"><a href="<?= e(url('/services/'.$s['slug'])) ?>"><?= e($s['name']) ?></a></h3><p style="font-size:.9rem"><?= e($s['summary']) ?></p></div></article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($roomTypes): ?>
        <h3 class="mt-4">Stay in</h3>
        <div class="grid grid-2 mt-2">
          <?php foreach ($roomTypes as $r) echo partial('partials.room-card', ['room' => $r]); ?>
        </div>
        <?php endif; ?>
      </div>
      <aside>
        <div class="booking-card">
          <span class="chip">Package</span>
          <div class="price mt-2"><?php if ($package['original_price'] && $package['original_price'] > $package['price']): ?><del><?= money($package['original_price']) ?></del> <?php endif; ?><?= money($package['price']) ?></div>
          <p class="muted" style="font-size:.85rem"><?= e($package['price_unit']) ?></p>
          <hr style="border:0;border-top:1px solid var(--line);margin:16px 0">
          <?php if ($roomTypes): ?>
            <a class="btn btn-primary btn-block" href="<?= e(url('/booking/'.$roomTypes[0]['slug'].'?offer_code='.urlencode($package['slug']))) ?>"><?= icon('calendar') ?> Book this package</a>
          <?php endif; ?>
          <a class="btn btn-outline btn-block mt-2" href="<?= e(url('/contact')) ?>">Ask a question</a>
        </div>
      </aside>
    </div>
  </div>
</section>
