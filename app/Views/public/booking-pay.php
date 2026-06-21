<?php /** @var array $booking @var array $room @var bool $xenditReady @var bool $paypalReady */ ?>
<?= partial('partials.page-hero', [
    'pageTitle' => 'Choose how to pay',
    'pageSub'   => 'Booking ' . $booking['reference'],
    'heroImage' => $room['cover'] ?? 'general/beach',
    'crumbs'    => ['Payment' => null],
]) ?>
<section class="section">
  <div class="container">
    <?= partial('partials.flash') ?>
    <div class="detail-layout">
      <div>
        <?php if (!$xenditReady && !$paypalReady): ?>
          <div class="alert alert-info"><?= icon('check','',16) ?> <strong>Sandbox mode:</strong> live payment keys aren't configured yet, so Xendit / PayPal will run a simulated sandbox payment (no real charge). Add your API keys in <code>config/config.local.php</code> to enable live processing.</div>
        <?php endif; ?>

        <div class="grid" style="gap:16px">
          <!-- Xendit -->
          <form method="post" action="<?= e(url('/booking/'.$booking['reference'].'/pay')) ?>">
            <?= csrf_field() ?><input type="hidden" name="method" value="xendit">
            <button class="card" type="submit" style="width:100%;text-align:left;border:1px solid var(--line);cursor:pointer">
              <div class="card__body" style="flex-direction:row;align-items:center;justify-content:space-between">
                <div><strong>Pay with Xendit</strong><div class="muted" style="font-size:.9rem">Cards, GCash, GrabPay, bank transfer &amp; more</div></div>
                <span class="btn btn-teal btn-sm">Continue <?= icon('chevron-right','',16) ?></span>
              </div>
            </button>
          </form>
          <!-- PayPal -->
          <form method="post" action="<?= e(url('/booking/'.$booking['reference'].'/pay')) ?>">
            <?= csrf_field() ?><input type="hidden" name="method" value="paypal">
            <button class="card" type="submit" style="width:100%;text-align:left;border:1px solid var(--line);cursor:pointer">
              <div class="card__body" style="flex-direction:row;align-items:center;justify-content:space-between">
                <div><strong>Pay with PayPal</strong><div class="muted" style="font-size:.9rem">PayPal balance or international cards</div></div>
                <span class="btn btn-outline btn-sm">Continue <?= icon('chevron-right','',16) ?></span>
              </div>
            </button>
          </form>
          <!-- Reserve / pay at hotel -->
          <form method="post" action="<?= e(url('/booking/'.$booking['reference'].'/pay')) ?>">
            <?= csrf_field() ?><input type="hidden" name="method" value="reserve">
            <button class="card" type="submit" style="width:100%;text-align:left;border:1px dashed var(--line);cursor:pointer;background:transparent;box-shadow:none">
              <div class="card__body" style="flex-direction:row;align-items:center;justify-content:space-between">
                <div><strong>Reserve &amp; pay at hotel</strong><div class="muted" style="font-size:.9rem">Hold your booking and settle on arrival</div></div>
                <span class="btn btn-sm" style="color:var(--teal-deep)">Reserve <?= icon('chevron-right','',16) ?></span>
              </div>
            </button>
          </form>
        </div>
      </div>
      <aside>
        <div class="booking-card">
          <h3 style="font-size:1.15rem"><?= e($room['name'] ?? 'Booking') ?></h3>
          <p class="muted" style="font-size:.9rem"><?= e(date('M j', strtotime($booking['check_in']))) ?> – <?= e(date('M j, Y', strtotime($booking['check_out']))) ?> · <?= (int)$booking['nights'] ?> nights · <?= (int)$booking['rooms_count'] ?> room(s)</p>
          <hr style="border:0;border-top:1px solid var(--line);margin:16px 0">
          <div class="flex" style="justify-content:space-between"><span class="muted">Subtotal</span><span><?= money($booking['subtotal']) ?></span></div>
          <?php if ($booking['discount'] > 0): ?><div class="flex mt-2" style="justify-content:space-between;color:var(--coral-deep)"><span>Discount</span><span>−<?= money($booking['discount']) ?></span></div><?php endif; ?>
          <hr style="border:0;border-top:1px solid var(--line);margin:16px 0">
          <div class="flex" style="justify-content:space-between;align-items:baseline"><strong>Total due</strong><span class="price"><?= money($booking['total']) ?></span></div>
        </div>
      </aside>
    </div>
  </div>
</section>
