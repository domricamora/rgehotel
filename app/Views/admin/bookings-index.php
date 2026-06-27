<?php /** @var array $bookings @var string|null $status */ ?>
<div class="panel">
  <div class="panel__head">
    <h2>All Bookings</h2>
    <div style="display:flex;gap:8px;align-items:center">
    <a class="btn btn-primary btn-sm" href="<?= e(url('/admin/bookings/new')) ?>"><?= icon('check') ?> New walk-in booking</a>
    <form method="get" style="display:flex;gap:8px">
      <select name="status" class="field" style="padding:8px 12px;border:1px solid var(--line);border-radius:0" onchange="this.form.submit()">
        <option value="">All statuses</option>
        <?php foreach (['pending','confirmed','checked_in','checked_out','cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Reference</th><th>Guest</th><th>Room</th><th>Check-in</th><th>Nights</th><th>Total</th><th>Status</th><th>Payment</th><th></th></tr></thead>
      <tbody>
        <?php if (!$bookings): ?><tr><td colspan="9" class="muted">No bookings found.</td></tr><?php endif; ?>
        <?php foreach ($bookings as $b): ?>
        <tr>
          <td><strong><?= e($b['reference']) ?></strong></td>
          <td><?= e($b['guest_name']) ?><div class="muted" style="font-size:.8rem"><?= e($b['guest_email']) ?></div></td>
          <td><?= e($b['room_name']) ?></td>
          <td><?= e(date('M j, Y', strtotime($b['check_in']))) ?></td>
          <td><?= (int)$b['nights'] ?></td>
          <?php $charges = (float)($b['charges_total'] ?? 0); $grand = (float)$b['total'] + $charges; ?>
          <td><?= money($grand) ?><?php if ($charges > 0): ?><div class="muted" style="font-size:.78rem">room <?= money($b['total']) ?> + charges <?= money($charges) ?></div><?php endif; ?></td>
          <td><?= badge($b['status']) ?></td>
          <td><?= badge($b['payment_status']) ?></td>
          <td><a class="btn btn-outline btn-sm" href="<?= e(url('/admin/bookings/'.$b['id'])) ?>">Open</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
