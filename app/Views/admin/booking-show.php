<?php
use App\Core\Auth;
/** @var array $b @var array $payments */
$canManage = Auth::can('bookings.manage');
?>
<div class="form-grid">
  <div>
    <div class="panel">
      <div class="panel__head"><h2>Guest & Stay</h2><?= badge($b['status']) ?></div>
      <div class="panel__body">
        <div class="form-grid">
          <div><div class="muted" style="font-size:.78rem">Guest</div><strong><?= e($b['guest_name']) ?></strong></div>
          <div><div class="muted" style="font-size:.78rem">Email</div><?= e($b['guest_email']) ?></div>
          <div><div class="muted" style="font-size:.78rem">Phone</div><?= e($b['guest_phone'] ?: '—') ?></div>
          <div><div class="muted" style="font-size:.78rem">Country</div><?= e($b['guest_country'] ?: '—') ?></div>
          <div><div class="muted" style="font-size:.78rem">Room</div><a href="<?= e(url('/accommodations/'.$b['room_slug'])) ?>" target="_blank"><?= e($b['room_name']) ?></a></div>
          <div><div class="muted" style="font-size:.78rem">Guests</div><?= (int)$b['adults'] ?> adult(s), <?= (int)$b['children'] ?> child(ren)</div>
          <div><div class="muted" style="font-size:.78rem">Check-in</div><?= e(date('D, M j, Y', strtotime($b['check_in']))) ?></div>
          <div><div class="muted" style="font-size:.78rem">Check-out</div><?= e(date('D, M j, Y', strtotime($b['check_out']))) ?></div>
          <div><div class="muted" style="font-size:.78rem">Nights / Rooms</div><?= (int)$b['nights'] ?> / <?= (int)$b['rooms_count'] ?></div>
          <div><div class="muted" style="font-size:.78rem">Total</div><strong><?= money($b['total']) ?></strong> <?php if($b['discount']>0):?><span class="muted">(−<?= money($b['discount']) ?>)</span><?php endif;?></div>
        </div>
        <?php if ($b['special_requests']): ?><div class="mt-2"><div class="muted" style="font-size:.78rem">Special requests</div><?= e($b['special_requests']) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel__head"><h2>Payments</h2></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Provider</th><th>Amount</th><th>Status</th><th>External ID</th><th>Date</th></tr></thead>
          <tbody>
            <?php if (!$payments): ?><tr><td colspan="5" class="muted">No payment records.</td></tr><?php endif; ?>
            <?php foreach ($payments as $p): ?>
            <tr><td><?= e(ucfirst($p['provider'])) ?></td><td><?= money($p['amount']) ?></td><td><?= badge($p['status']) ?></td><td class="muted" style="font-size:.82rem"><?= e($p['external_id'] ?: '—') ?></td><td class="muted" style="font-size:.82rem"><?= e(date('M j, H:i', strtotime($p['created_at']))) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div>
    <div class="panel">
      <div class="panel__head"><h2>Manage</h2></div>
      <div class="panel__body">
        <?php if ($canManage): ?>
        <form method="post" action="<?= e(url('/admin/bookings/'.$b['id'])) ?>">
          <?= csrf_field() ?>
          <div class="field"><label>Booking status</label>
            <select name="status"><?php foreach (['pending','confirmed','checked_in','checked_out','cancelled'] as $s): ?><option value="<?= $s ?>" <?= $b['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option><?php endforeach; ?></select>
          </div>
          <div class="field mt-2"><label>Payment status</label>
            <select name="payment_status"><?php foreach (['unpaid','paid','partial','refunded','failed'] as $s): ?><option value="<?= $s ?>" <?= $b['payment_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select>
          </div>
          <div class="field mt-2"><label>Notes / requests</label><textarea name="special_requests" rows="3"><?= e($b['special_requests']) ?></textarea></div>
          <button class="btn btn-primary" type="submit"><?= icon('check') ?> Save changes</button>
        </form>
        <?php else: ?><p class="muted">You have view-only access to bookings.</p><?php endif; ?>
      </div>
    </div>
    <a class="btn btn-outline" href="<?= e(url('/admin/bookings')) ?>">← Back to bookings</a>
  </div>
</div>
