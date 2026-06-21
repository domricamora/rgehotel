<?php
/** @var array $services */
$groups = [];
foreach ($services as $s) { $groups[$s['category']][] = $s; }
$labels = ['island_hopping'=>'Island Hopping','tour'=>'Tours','diving'=>'Diving','watersport'=>'Water Sports','transfer'=>'Transfers','car'=>'Car Rental','spa'=>'Spa','other'=>'Other'];
?>
<?= partial('partials.page-hero', [
    'pageTitle' => 'Tours & Services',
    'pageSub'   => 'Kalanggaman Island hopping, Leyte heritage tours, diving, water sports and more — all arranged by our team.',
    'heroImage' => 'general/aerial',
    'crumbs'    => ['Tours & Services' => null],
]) ?>
<section class="section">
  <div class="container">
    <?php foreach ($groups as $cat => $items): ?>
      <div class="section-head" style="margin-top:24px"><span class="eyebrow"><?= e($labels[$cat] ?? ucfirst($cat)) ?></span><h2><?= e($labels[$cat] ?? ucfirst($cat)) ?></h2></div>
      <div class="grid grid-3">
        <?php foreach ($items as $s): ?>
        <article class="card reveal">
          <a class="card__media" href="<?= e(url('/services/'.$s['slug'])) ?>"><?= img_tag($s['image'] ?? null, $s['name']) ?></a>
          <div class="card__body">
            <h3 style="font-size:1.15rem"><a href="<?= e(url('/services/'.$s['slug'])) ?>"><?= e($s['name']) ?></a></h3>
            <p style="font-size:.94rem"><?= e($s['summary']) ?></p>
            <div class="card__meta"><?php if($s['duration']):?><span><?= icon('clock','',15) ?> <?= e($s['duration']) ?></span><?php endif;?></div>
            <div class="card__foot">
              <?php if ($s['price']): ?><div class="price" style="font-size:1.15rem"><?= money($s['price']) ?> <small><?= e($s['price_unit']) ?></small></div><?php endif; ?>
              <a class="btn btn-outline btn-sm" href="<?= e(url('/services/'.$s['slug'])) ?>">Details <?= icon('chevron-right','',16) ?></a>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>
