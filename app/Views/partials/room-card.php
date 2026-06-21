<?php /** @var array $room */ ?>
<article class="card reveal">
  <a class="card__media" href="<?= e(url('/accommodations/' . $room['slug'])) ?>">
    <?= img_tag($room['cover'] ?? null, $room['name'], '', '(max-width:640px) 100vw, 33vw') ?>
    <?php if (!empty($room['is_featured'])): ?><span class="card__badge">Featured</span><?php endif; ?>
    <?php if (isset($room['available'])): ?>
      <span class="card__badge" style="left:auto;right:14px;<?= $room['available'] > 0 ? '' : 'color:#b3261e' ?>">
        <?= $room['available'] > 0 ? $room['available'] . ' left' : 'Sold out' ?>
      </span>
    <?php endif; ?>
  </a>
  <div class="card__body">
    <div class="card__meta">
      <span><?= icon('users','',15) ?> <?= (int)$room['max_occupancy'] ?> guests</span>
      <?php if ($room['beds']): ?><span><?= icon('bed','',15) ?> <?= e($room['beds']) ?></span><?php endif; ?>
      <?php if ($room['view']): ?><span><?= icon('waves','',15) ?> <?= e($room['view']) ?></span><?php endif; ?>
    </div>
    <h3><a href="<?= e(url('/accommodations/' . $room['slug'])) ?>"><?= e($room['name']) ?></a></h3>
    <p style="font-size:.95rem"><?= e($room['summary']) ?></p>
    <div class="card__foot">
      <div class="price"><?= money($room['base_price']) ?> <small>/ night</small></div>
      <a class="btn btn-outline btn-sm" href="<?= e(url('/accommodations/' . $room['slug'])) ?>">View <?= icon('chevron-right','',16) ?></a>
    </div>
  </div>
</article>
