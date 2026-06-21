<?php
/** @var array $services */
$labels = ['island_hopping'=>'Island Hopping','tour'=>'Tour','diving'=>'Diving','watersport'=>'Water Sports','transfer'=>'Transfer','car'=>'Car Rental','spa'=>'Spa','other'=>'Other'];
?>
<?= partial('partials.page-hero', [
    'pageTitle' => 'Tours & Services',
    'pageSub'   => 'Kalanggaman Island hopping, Leyte heritage tours, diving, water sports and more — all arranged by our team.',
    'heroImage' => 'general/services-hero',
    'crumbs'    => ['Tours & Services' => null],
]) ?>
<section class="section">
  <div class="container">
    <div class="grid grid-3">
      <?php foreach ($services as $s): ?>
      <article class="card reveal">
        <a class="card__media" href="<?= e(url('/services/'.$s['slug'])) ?>">
          <?= img_tag($s['image'] ?? null, $s['name'], '', '(max-width:640px) 100vw, 33vw') ?>
          <span class="card__badge"><?= e($labels[$s['category']] ?? ucfirst($s['category'])) ?></span>
        </a>
        <div class="card__body">
          <h3 style="font-size:1.15rem"><a href="<?= e(url('/services/'.$s['slug'])) ?>"><?= e($s['name']) ?></a></h3>
          <p style="font-size:.94rem"><?= e($s['summary']) ?></p>
          <div class="card__meta"><?php if($s['duration']):?><span><?= icon('clock','',15) ?> <?= e($s['duration']) ?></span><?php endif;?></div>
          <div class="card__foot">
            <?php if ($s['price']): ?><div class="price" style="font-size:1.15rem"><?= money($s['price']) ?> <small><?= e($s['price_unit']) ?></small></div><?php else: ?><span></span><?php endif; ?>
            <a class="btn btn-outline btn-sm" href="<?= e(url('/services/'.$s['slug'])) ?>">Details <?= icon('chevron-right','',16) ?></a>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
