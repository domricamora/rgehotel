<?php /** @var array $stats @var array $recent @var array $upcoming */ ?>
<div class="stats">
  <div class="stat"><div class="label"><?= icon('calendar','',16) ?> Total Bookings</div><div class="num"><?= (int)$stats['bookings'] ?></div><div class="sub"><?= (int)$stats['pending'] ?> pending</div></div>
  <div class="stat"><div class="label"><?= icon('percent','',16) ?> Paid Revenue</div><div class="num"><?= money($stats['revenue']) ?></div><div class="sub">confirmed payments</div></div>
  <div class="stat"><div class="label"><?= icon('bed','',16) ?> Occupancy</div><div class="num"><?= (int)$stats['occupied'] ?>/<?= (int)$stats['units'] ?></div><div class="sub">rooms occupied</div></div>
  <div class="stat"><div class="label"><?= icon('star','',16) ?> To Review</div><div class="num"><?= (int)$stats['pending_reviews'] ?></div><div class="sub"><?= (int)$stats['messages'] ?> new messages</div></div>
</div>

<div class="panel">
  <div class="panel__head"><h2>Recent Bookings</h2><a class="btn btn-outline btn-sm" href="<?= e(url('/admin/bookings')) ?>">View all</a></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Reference</th><th>Guest</th><th>Room</th><th>Dates</th><th>Total</th><th>Status</th><th>Payment</th><th></th></tr></thead>
      <tbody>
        <?php if (!$recent): ?><tr><td colspan="8" class="muted">No bookings yet.</td></tr><?php endif; ?>
        <?php foreach ($recent as $b): ?>
        <tr>
          <td><strong><?= e($b['reference']) ?></strong></td>
          <td><?= e($b['guest_name']) ?><div class="muted" style="font-size:.8rem"><?= e($b['guest_email']) ?></div></td>
          <td><?= e($b['room_name']) ?></td>
          <td><?= e(date('M j', strtotime($b['check_in']))) ?>–<?= e(date('M j', strtotime($b['check_out']))) ?></td>
          <td><?= money($b['total']) ?></td>
          <td><?= badge($b['status']) ?></td>
          <td><?= badge($b['payment_status']) ?></td>
          <td><a class="btn btn-outline btn-sm" href="<?= e(url('/admin/bookings/'.$b['id'])) ?>">Open</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="panel">
  <div class="panel__head"><h2>Upcoming Arrivals</h2></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Check-in</th><th>Guest</th><th>Room</th><th>Nights</th><th>Status</th></tr></thead>
      <tbody>
        <?php if (!$upcoming): ?><tr><td colspan="5" class="muted">No upcoming arrivals.</td></tr><?php endif; ?>
        <?php foreach ($upcoming as $b): ?>
        <tr><td><strong><?= e(date('M j, Y', strtotime($b['check_in']))) ?></strong></td><td><?= e($b['guest_name']) ?></td><td><?= e($b['room_name']) ?></td><td><?= (int)$b['nights'] ?></td><td><?= badge($b['status']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
