<?php /** @var array $service @var array $related */ ?>
<?= partial('partials.page-hero', [
    'pageTitle' => $service['name'],
    'pageSub'   => $service['summary'],
    'heroImage' => $service['image'] ?? 'general/aerial',
    'crumbs'    => ['Tours & Services' => url('/services'), $service['name'] => null],
]) ?>
<section class="section">
  <div class="container">
    <div class="detail-layout">
      <div>
        <?php if ($service['image']): ?><?= img_tag($service['image'], $service['name'], '', '66vw') ?><?php endif; ?>
        <h2 class="mt-4" style="font-size:1.5rem">Overview</h2>
        <p class="mt-2" style="white-space:pre-line"><?= e($service['description']) ?></p>
        <?php if ($service['highlights']): ?>
          <h3 class="mt-4">Highlights</h3>
          <ul class="amenity-list mt-2">
            <?php foreach (preg_split('/\r?\n/', $service['highlights']) as $h): if(trim($h)==='')continue; ?>
              <li><?= icon('check') ?> <?= e(trim($h)) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
      <aside>
        <div class="booking-card">
          <?php if ($service['price']): ?><div class="price"><?= money($service['price']) ?> <small><?= e($service['price_unit']) ?></small></div><?php endif; ?>
          <?php if ($service['duration']): ?><p class="muted mt-2"><?= icon('clock','',16) ?> <?= e($service['duration']) ?></p><?php endif; ?>
          <hr style="border:0;border-top:1px solid var(--line);margin:16px 0">
          <p style="font-size:.92rem">Add this experience to your stay — our team will arrange everything.</p>
          <a class="btn btn-primary btn-block mt-3" href="<?= e(url('/contact')) ?>"><?= icon('mail') ?> Enquire / Book</a>
          <a class="btn btn-outline btn-block mt-2" href="<?= e(url('/accommodations')) ?>">Book a room first</a>
        </div>
      </aside>
    </div>

    <?php if ($related): ?>
    <h3 class="mt-4">You might also like</h3>
    <div class="grid grid-3 mt-2">
      <?php foreach ($related as $s): ?>
      <article class="card"><a class="card__media" href="<?= e(url('/services/'.$s['slug'])) ?>"><?= img_tag($s['image'] ?? null, $s['name']) ?></a>
        <div class="card__body"><h3 style="font-size:1.05rem"><a href="<?= e(url('/services/'.$s['slug'])) ?>"><?= e($s['name']) ?></a></h3>
        <div class="card__foot"><?php if($s['price']):?><span class="price" style="font-size:1.05rem"><?= money($s['price']) ?></span><?php endif;?><a class="btn btn-outline btn-sm" href="<?= e(url('/services/'.$s['slug'])) ?>">View</a></div></div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
