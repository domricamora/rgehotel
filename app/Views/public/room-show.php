<?php
/** @var array $room @var array $photos @var array $amenities @var array $packages @var array $offers @var array $reviews */
$defaultIn = date('Y-m-d', strtotime('+7 days'));
$defaultOut = date('Y-m-d', strtotime('+9 days'));
?>
<?= partial('partials.page-hero', [
    'pageTitle' => $room['name'],
    'pageSub'   => $room['summary'],
    'heroImage' => $room['cover'] ?? 'general/beach',
    'crumbs'    => ['Accommodations' => url('/accommodations'), $room['name'] => null],
]) ?>

<section class="section">
  <div class="container">
    <?= partial('partials.flash') ?>

    <?php if ($photos): ?>
    <div class="gallery mb-3">
      <?php foreach (array_slice($photos, 0, 5) as $ph): ?>
        <a href="<?= e(img_url($ph['filename'], 'full')) ?>" data-lightbox>
          <?= img_tag($ph['filename'], $ph['alt'] ?? $room['name'], '', '(max-width:700px) 100vw, 50vw') ?>
        </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="detail-layout mt-4">
      <div>
        <div class="card__meta" style="font-size:.95rem;margin-bottom:18px">
          <span><?= icon('users','',17) ?> Sleeps <?= (int)$room['max_occupancy'] ?></span>
          <?php if ($room['beds']): ?><span><?= icon('bed','',17) ?> <?= e($room['beds']) ?></span><?php endif; ?>
          <?php if ($room['size_sqm']): ?><span><?= icon('maximize','',17) ?> <?= (int)$room['size_sqm'] ?> m²</span><?php endif; ?>
          <?php if ($room['view']): ?><span><?= icon('waves','',17) ?> <?= e($room['view']) ?></span><?php endif; ?>
          <?php if ($room['avg_rating']): ?><span><?= stars($room['avg_rating']) ?> <?= e($room['avg_rating']) ?> (<?= (int)$room['review_count'] ?>)</span><?php endif; ?>
        </div>

        <h2 style="font-size:1.6rem">About this room</h2>
        <p class="mt-2" style="white-space:pre-line"><?= e($room['description']) ?></p>

        <?php if ($amenities): ?>
        <h3 class="mt-4">Amenities</h3>
        <ul class="amenity-list mt-2">
          <?php foreach ($amenities as $a): ?>
            <li><?= icon($a['icon'] ?: 'check') ?> <?= e($a['name']) ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <?php if ($offers): ?>
        <h3 class="mt-4">Ongoing offers</h3>
        <div class="grid grid-2 mt-2">
          <?php foreach ($offers as $o): ?>
          <div class="card" style="border-color:#e9d8c5"><div class="card__body">
            <span class="chip" style="background:#f6ece2;color:var(--coral-deep)"><?= icon('percent','',15) ?> <?= $o['discount_type']==='percent' ? (int)$o['discount_value'].'% OFF' : money($o['discount_value']).' OFF' ?></span>
            <strong><?= e($o['title']) ?></strong>
            <p style="font-size:.9rem"><?= e($o['subtitle']) ?></p>
            <?php if ($o['code']): ?><span>Use code <code style="background:var(--sand);padding:2px 7px;border-radius:0"><?= e($o['code']) ?></code></span><?php endif; ?>
          </div></div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($packages): ?>
        <h3 class="mt-4">Related packages</h3>
        <div class="grid grid-2 mt-2">
          <?php foreach ($packages as $p): ?>
          <article class="card"><a class="card__media" href="<?= e(url('/packages/'.$p['slug'])) ?>"><?= img_tag($p['image'] ?? $room['cover'], $p['name']) ?></a>
            <div class="card__body"><h3 style="font-size:1.05rem"><a href="<?= e(url('/packages/'.$p['slug'])) ?>"><?= e($p['name']) ?></a></h3>
            <p style="font-size:.9rem"><?= e($p['summary']) ?></p>
            <div class="card__foot"><span class="price" style="font-size:1.05rem"><?= money($p['price']) ?></span><a class="btn btn-outline btn-sm" href="<?= e(url('/packages/'.$p['slug'])) ?>">View</a></div></div>
          </article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($reviews): ?>
        <h3 class="mt-4">Guest reviews</h3>
        <div class="grid grid-2 mt-2">
          <?php foreach ($reviews as $rv): ?>
          <div class="review"><div class="review__head"><span class="review__name"><?= e($rv['author_name']) ?> <span class="review__country">· <?= e($rv['author_country']) ?></span></span><?= stars($rv['rating']) ?></div>
            <strong><?= e($rv['title']) ?></strong><p style="font-size:.92rem;margin-top:4px">“<?= e($rv['body']) ?>”</p></div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Booking card -->
      <aside>
        <form class="booking-card" method="get" action="<?= e(url('/booking/'.$room['slug'])) ?>">
          <div class="price"><?= money($room['base_price']) ?> <small>/ night</small></div>
          <?php if ($room['weekend_price'] && $room['weekend_price'] != $room['base_price']): ?>
            <p class="muted" style="font-size:.85rem"><?= money($room['weekend_price']) ?> on weekends</p>
          <?php endif; ?>
          <hr style="border:0;border-top:1px solid var(--line);margin:16px 0">
          <div class="field-row">
            <div class="field"><label>Check-in</label><input type="date" name="check_in" value="<?= e($defaultIn) ?>" required></div>
            <div class="field"><label>Check-out</label><input type="date" name="check_out" value="<?= e($defaultOut) ?>" required></div>
          </div>
          <div class="field-row">
            <div class="field"><label>Adults</label><select name="adults"><?php for($i=1;$i<=$room['max_occupancy'];$i++) echo "<option>$i</option>"; ?></select></div>
            <div class="field"><label>Rooms</label><select name="rooms"><?php for($i=1;$i<=min(5,$room['total_units']);$i++) echo "<option>$i</option>"; ?></select></div>
          </div>
          <button class="btn btn-primary btn-block" type="submit"><?= icon('calendar') ?> Check Availability</button>
          <p class="muted center" style="font-size:.8rem;margin-top:12px">You won't be charged yet</p>
        </form>
      </aside>
    </div>
  </div>
</section>
