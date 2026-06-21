<?php
use App\Models\Folio;
/** @var array $booking @var array $room @var array $charges @var array $summary @var bool $xenditReady */
?>
<?= partial('partials.page-hero', [
    'pageTitle' => 'Your bill',
    'pageSub'   => 'Booking ' . $booking['reference'],
    'heroImage' => $room['cover'] ?? 'general/beach',
    'crumbs'    => ['Billing' => null],
]) ?>
<section class="section">
  <div class="container">
    <?= partial('partials.flash') ?>
    <div class="detail-layout">
      <div>
        <div class="panel" style="border:1px solid var(--line);border-radius:0">
          <div class="panel__body">
            <h2 style="font-size:1.15rem;margin:0 0 4px">Folio for <?= e($booking['guest_name']) ?></h2>
            <p class="muted" style="font-size:.9rem;margin:0">
              <?= e($room['name'] ?? 'Room') ?> ·
              <?= e(date('M j', strtotime($booking['check_in']))) ?> – <?= e(date('M j, Y', strtotime($booking['check_out']))) ?>
            </p>
          </div>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Date</th><th>Item</th><th style="text-align:center">Qty</th><th style="text-align:right">Amount</th></tr></thead>
              <tbody>
                <tr>
                  <td class="muted" style="font-size:.85rem"><?= e(date('M j', strtotime($booking['created_at']))) ?></td>
                  <td><?= e($room['name'] ?? 'Room') ?> — <?= (int)$booking['nights'] ?> night(s)</td>
                  <td style="text-align:center"><?= (int)$booking['rooms_count'] ?></td>
                  <td style="text-align:right"><?= money($booking['subtotal']) ?></td>
                </tr>
                <?php if ($booking['discount'] > 0): ?>
                <tr><td></td><td style="color:var(--coral-deep)">Discount</td><td></td><td style="text-align:right;color:var(--coral-deep)">−<?= money($booking['discount']) ?></td></tr>
                <?php endif; ?>
                <?php foreach ($charges as $c): ?>
                <tr>
                  <td class="muted" style="font-size:.85rem"><?= e(date('M j', strtotime($c['charged_at']))) ?></td>
                  <td><?= e($c['description']) ?> <span class="muted" style="font-size:.82rem">(<?= e(Folio::categoryLabel($c['category'])) ?>)</span></td>
                  <td style="text-align:center"><?= rtrim(rtrim(number_format((float)$c['quantity'],2),'0'),'.') ?></td>
                  <td style="text-align:right"><?= money($c['amount']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <aside>
        <div class="booking-card">
          <h3 style="font-size:1.15rem">Bill summary</h3>
          <hr style="border:0;border-top:1px solid var(--line);margin:16px 0">
          <div class="flex" style="justify-content:space-between"><span class="muted">Room</span><span><?= money($summary['room_total']) ?></span></div>
          <div class="flex mt-2" style="justify-content:space-between"><span class="muted">In-house charges</span><span><?= money($summary['charges_total']) ?></span></div>
          <div class="flex mt-2" style="justify-content:space-between"><strong>Grand total</strong><span><?= money($summary['grand_total']) ?></span></div>
          <div class="flex mt-2" style="justify-content:space-between;color:var(--teal-deep)"><span>Paid</span><span><?= money($summary['paid']) ?></span></div>
          <hr style="border:0;border-top:1px solid var(--line);margin:16px 0">
          <div class="flex" style="justify-content:space-between;align-items:baseline"><strong>Balance due</strong><span class="price"><?= money($summary['balance']) ?></span></div>

          <?php if ($summary['balance'] > 0): ?>
            <?php if (!$xenditReady): ?>
              <div class="alert alert-info mt-3" style="font-size:.85rem"><?= icon('check','',14) ?> <strong>Sandbox mode:</strong> live keys aren't configured, so payment is simulated (no real charge).</div>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/booking/'.$booking['reference'].'/billing/pay')) ?>" class="mt-3">
              <?= csrf_field() ?>
              <button class="btn btn-teal" type="submit" style="width:100%">Pay <?= money($summary['balance']) ?> with Xendit <?= icon('chevron-right','',16) ?></button>
            </form>
            <p class="muted mt-2" style="font-size:.82rem;text-align:center">Cards, GCash, GrabPay, bank transfer &amp; more. You can also settle at the front desk.</p>
          <?php else: ?>
            <div class="alert alert-success mt-3" style="font-size:.9rem"><?= icon('check','',16) ?> Your bill is fully settled. Thank you!</div>
          <?php endif; ?>
        </div>
      </aside>
    </div>
  </div>
</section>
