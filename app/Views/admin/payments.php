<?php /** @var array $payments @var array $summary */ ?>
<div class="stats">
  <div class="stat"><div class="label"><?= icon('percent','',16) ?> Total Collected</div><div class="num"><?= money($summary['paid']) ?></div><div class="sub"><?= (int)$summary['count'] ?> paid transactions</div></div>
  <div class="stat"><div class="label"><?= icon('clock','',16) ?> Pending</div><div class="num"><?= (int)$summary['pending'] ?></div><div class="sub">awaiting confirmation</div></div>
</div>
<div class="panel">
  <div class="panel__head"><h2>Transactions</h2></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Booking</th><th>Guest</th><th>Provider</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody>
        <?php if (!$payments): ?><tr><td colspan="6" class="muted">No payments yet.</td></tr><?php endif; ?>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td class="muted" style="font-size:.85rem"><?= e(date('M j, Y H:i', strtotime($p['created_at']))) ?></td>
          <td><a href="<?= e(url('/admin/bookings/'.$p['booking_id'])) ?>"><?= e($p['reference']) ?></a></td>
          <td><?= e($p['guest_name']) ?></td>
          <td><?= e(ucfirst($p['provider'])) ?></td>
          <td><?= money($p['amount']) ?></td>
          <td><?= badge($p['status']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
