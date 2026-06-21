<?php /** @var array $booking @var array $room */
$paid = $booking['payment_status'] === 'paid';
?>
<section class="section">
  <div class="container" style="max-width:680px">
    <?= partial('partials.flash') ?>
    <div class="card" style="text-align:center;padding:8px">
      <div class="card__body" style="align-items:center">
        <div class="ico" style="width:68px;height:68px;border-radius:50%;background:var(--teal-soft);color:var(--teal-deep);display:grid;place-items:center;margin-bottom:8px"><?= icon('check','',32) ?></div>
        <span class="eyebrow"><?= $paid ? 'Payment received' : 'Reservation held' ?></span>
        <h1 style="font-size:2rem"><?= $paid ? 'Booking confirmed!' : 'Almost there!' ?></h1>
        <p class="mt-2"><?= $paid
          ? 'Thank you! Your stay is confirmed. A confirmation has been sent to your email.'
          : 'Your reservation is held. Please settle payment on arrival to confirm your stay.' ?></p>

        <div class="alert alert-info mt-3" style="width:100%;text-align:left">
          <strong>Reference:</strong> <?= e($booking['reference']) ?><br>
          <strong>Room:</strong> <?= e($room['name'] ?? '') ?><br>
          <strong>Dates:</strong> <?= e(date('M j', strtotime($booking['check_in']))) ?> – <?= e(date('M j, Y', strtotime($booking['check_out']))) ?> (<?= (int)$booking['nights'] ?> nights)<br>
          <strong>Guests:</strong> <?= (int)$booking['adults'] ?> adult(s)<?= $booking['children'] ? ', '.(int)$booking['children'].' child(ren)' : '' ?> · <?= (int)$booking['rooms_count'] ?> room(s)<br>
          <strong>Total:</strong> <?= money($booking['total']) ?> · <strong>Status:</strong> <?= e(ucfirst($booking['payment_status'])) ?>
        </div>

        <div class="flex items-center gap-2 wrap mt-2" style="justify-content:center">
          <a class="btn btn-primary" href="<?= e(url('/')) ?>"><?= icon('arrow-right') ?> Back to Home</a>
          <a class="btn btn-outline" href="<?= e(url('/services')) ?>">Add an experience</a>
        </div>
      </div>
    </div>
    <p class="muted center mt-3" style="font-size:.85rem">Questions about your booking? Email <a href="mailto:info@rgehotel.com" style="color:var(--teal-deep)">info@rgehotel.com</a> with your reference.</p>
  </div>
</section>
