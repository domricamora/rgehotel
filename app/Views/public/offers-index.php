<?php /** @var array $offers */ ?>
<?= partial('partials.page-hero', [
    'pageTitle' => 'Special Offers',
    'pageSub'   => 'Save more on your island escape with our current promotions.',
    'heroImage' => 'general/beach',
    'crumbs'    => ['Offers' => null],
]) ?>
<section class="section">
  <div class="container">
    <div class="grid grid-2">
      <?php foreach ($offers as $o): ?>
      <article class="card reveal" style="border-color:#e9d8c5">
        <?php if ($o['image']): ?><div class="card__media"><?= img_tag($o['image'], $o['title']) ?>
          <span class="card__badge" style="background:var(--coral);color:#fff"><?= $o['discount_type']==='percent' ? (int)$o['discount_value'].'% OFF' : money($o['discount_value']).' OFF' ?></span></div><?php endif; ?>
        <div class="card__body">
          <h3><?= e($o['title']) ?></h3>
          <p style="font-size:.95rem"><?= e($o['description']) ?></p>
          <?php if ($o['ends_at']): ?><p class="muted" style="font-size:.85rem"><?= icon('clock','',15) ?> Valid until <?= e(date('M j, Y', strtotime($o['ends_at']))) ?></p><?php endif; ?>
          <div class="card__foot">
            <?php if ($o['code']): ?><span class="chip"><?= icon('tag','',15) ?> <?= e($o['code']) ?></span><?php endif; ?>
            <a class="btn btn-primary btn-sm" href="<?= e(url('/accommodations')) ?>">Book &amp; save</a>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
