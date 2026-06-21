<?php
/** @var array $room @var string $check_in @var string $check_out @var int $rooms @var int $available @var array $quote @var array|null $offer */
?>
<?= partial('partials.page-hero', [
    'pageTitle' => 'Complete your booking',
    'pageSub'   => $room['name'],
    'heroImage' => $room['cover'] ?? 'general/beach',
    'crumbs'    => ['Accommodations' => url('/accommodations'), $room['name'] => url('/accommodations/'.$room['slug']), 'Book' => null],
]) ?>
<section class="section">
  <div class="container">
    <?= partial('partials.flash') ?>
    <?php if ($available < 1): ?>
      <div class="alert alert-error">Sorry, this room is fully booked for the selected dates. Please try different dates.</div>
    <?php endif; ?>
    <div class="detail-layout">
      <div>
        <form method="post" action="<?= e(url('/booking/'.$room['slug'])) ?>" id="bookingForm">
          <?= csrf_field() ?>
          <input type="hidden" name="offer_code" value="<?= e($offer['code'] ?? '') ?>">
          <h2 style="font-size:1.4rem">Your stay</h2>
          <div class="field-row mt-2">
            <div class="field"><label>Check-in</label><input type="date" name="check_in" value="<?= e($check_in) ?>" required></div>
            <div class="field"><label>Check-out</label><input type="date" name="check_out" value="<?= e($check_out) ?>" required></div>
          </div>
          <div class="field-row">
            <div class="field"><label>Adults</label><select name="adults"><?php for($i=1;$i<=$room['max_occupancy'];$i++){$s=$i==($adults??1)?' selected':'';echo "<option$s>$i</option>";} ?></select></div>
            <div class="field"><label>Children</label><select name="children"><?php for($i=0;$i<=6;$i++){$s=$i==($children??0)?' selected':'';echo "<option$s>$i</option>";} ?></select></div>
          </div>
          <div class="field"><label>Rooms (max <?= (int)$available ?> available)</label>
            <select name="rooms"><?php for($i=1;$i<=max(1,$available);$i++){$s=$i==$rooms?' selected':'';echo "<option$s>$i</option>";} ?></select></div>

          <h2 style="font-size:1.4rem" class="mt-4">Guest details</h2>
          <div class="field-row mt-2">
            <div class="field"><label>Full name *</label><input name="guest_name" value="<?= e(old('guest_name')) ?>" required></div>
            <div class="field"><label>Email *</label><input type="email" name="guest_email" value="<?= e(old('guest_email')) ?>" required></div>
          </div>
          <div class="field-row">
            <div class="field"><label>Phone</label><input name="guest_phone"></div>
            <div class="field"><label>Country</label><input name="guest_country" placeholder="e.g. Philippines"></div>
          </div>
          <div class="field"><label>Special requests</label><textarea name="special_requests" rows="3" placeholder="Arrival time, dietary needs, etc."></textarea></div>
          <button class="btn btn-primary btn-block" type="submit" <?= $available<1?'disabled':'' ?>><?= icon('arrow-right') ?> Continue to Payment</button>
        </form>
      </div>
      <aside>
        <div class="booking-card">
          <div class="card__media" style="border-radius:0;overflow:hidden;aspect-ratio:16/10;margin-bottom:16px"><?= img_tag($room['cover'] ?? null, $room['name']) ?></div>
          <h3 style="font-size:1.15rem"><?= e($room['name']) ?></h3>
          <?php if ($offer): ?><span class="chip" style="background:#f6ece2;color:var(--coral-deep);margin-top:8px"><?= icon('tag','',14) ?> <?= e($offer['title']) ?></span><?php endif; ?>
          <hr style="border:0;border-top:1px solid var(--line);margin:16px 0">
          <div class="flex" style="justify-content:space-between"><span class="muted"><?= money($quote['rate']) ?> × <?= (int)$quote['nights'] ?> nights × <?= (int)$quote['rooms'] ?></span><span><?= money($quote['subtotal']) ?></span></div>
          <?php if ($quote['discount'] > 0): ?><div class="flex mt-2" style="justify-content:space-between;color:var(--coral-deep)"><span>Discount</span><span>−<?= money($quote['discount']) ?></span></div><?php endif; ?>
          <hr style="border:0;border-top:1px solid var(--line);margin:16px 0">
          <div class="flex" style="justify-content:space-between;align-items:baseline"><strong>Total</strong><span class="price"><?= money($quote['total']) ?></span></div>
          <p class="muted center" style="font-size:.8rem;margin-top:12px">Taxes included · pay securely next step</p>
        </div>
      </aside>
    </div>
  </div>
</section>
