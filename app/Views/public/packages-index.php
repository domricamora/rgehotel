<?php /** @var array $packages */ ?>
<?= partial('partials.page-hero', [
    'pageTitle' => 'Packages & Deals',
    'pageSub'   => 'Curated stays that bundle your room with breakfast and the best of Leyte.',
    'heroImage' => 'general/sunset',
    'crumbs'    => ['Packages' => null],
]) ?>
<section class="section">
  <div class="container">
    <div class="grid grid-3">
      <?php foreach ($packages as $p): ?>
      <article class="card reveal">
        <a class="card__media" href="<?= e(url('/packages/'.$p['slug'])) ?>"><?= img_tag($p['image'] ?? null, $p['name']) ?>
          <?php if ($p['original_price'] && $p['original_price'] > $p['price']): ?><span class="card__badge">Save <?= money($p['original_price'] - $p['price']) ?></span><?php endif; ?>
        </a>
        <div class="card__body">
          <h3><a href="<?= e(url('/packages/'.$p['slug'])) ?>"><?= e($p['name']) ?></a></h3>
          <p style="font-size:.95rem"><?= e($p['summary']) ?></p>
          <div class="card__meta"><?php if($p['nights']):?><span><?= icon('calendar','',15) ?> <?= (int)$p['nights'] ?> nights</span><?php endif;?><?php if($p['pax']):?><span><?= icon('users','',15) ?> <?= (int)$p['pax'] ?> pax</span><?php endif;?></div>
          <div class="card__foot">
            <div class="price"><?php if ($p['original_price'] && $p['original_price'] > $p['price']): ?><del><?= money($p['original_price']) ?></del><?php endif; ?><?= money($p['price']) ?></div>
            <a class="btn btn-primary btn-sm" href="<?= e(url('/packages/'.$p['slug'])) ?>">View deal</a>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
