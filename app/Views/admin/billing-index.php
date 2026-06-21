<?php
/** @var array $rows @var array $summary @var string $filter @var bool $canManage */
$f = $filter === 'outstanding' ? 'outstanding' : 'all';
?>
<div class="stats">
  <div class="stat"><div class="label"><?= icon('clock','',16) ?> Outstanding balance</div><div class="num" style="color:<?= $summary['outstanding']>0?'#9a6a00':'#14653b' ?>"><?= money($summary['outstanding']) ?></div><div class="sub"><?= (int)$summary['guests_with_balance'] ?> guest(s) with a balance</div></div>
  <div class="stat"><div class="label"><?= icon('users','',16) ?> In-house / active</div><div class="num"><?= (int)$summary['count'] ?></div><div class="sub">pending · confirmed · checked-in</div></div>
  <div class="stat"><div class="label"><?= icon('calendar','',16) ?> Due to check out today</div><div class="num"><?= (int)$summary['due_today'] ?></div><div class="sub">by check-out date</div></div>
</div>

<div class="panel">
  <div class="panel__head">
    <h2>Guest Billing</h2>
    <div style="display:flex;gap:8px">
      <a class="btn btn-sm <?= $f==='all'?'btn-primary':'btn-outline' ?>" href="<?= e(url('/admin/billing')) ?>">All active</a>
      <a class="btn btn-sm <?= $f==='outstanding'?'btn-primary':'btn-outline' ?>" href="<?= e(url('/admin/billing?filter=outstanding')) ?>">Outstanding only</a>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>Guest</th><th>Room</th><th>Check-out</th>
        <th style="text-align:right">Room</th><th style="text-align:right">Charges</th>
        <th style="text-align:right">Paid</th><th style="text-align:right">Balance</th>
        <th>Actions</th>
      </tr></thead>
      <tbody>
        <?php if (!$rows): ?><tr><td colspan="8" class="muted">No active guests<?= $f==='outstanding'?' with an outstanding balance':'' ?>.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): $due = date('Y-m-d', strtotime($r['check_out'])) === date('Y-m-d'); ?>
        <tr>
          <td>
            <a href="<?= e(url('/admin/bookings/'.$r['id'])) ?>"><strong><?= e($r['guest_name']) ?></strong></a>
            <div class="muted" style="font-size:.78rem"><?= e($r['reference']) ?> · <?= badge($r['status']) ?></div>
          </td>
          <td><?= e($r['room_name']) ?></td>
          <td><?= e(date('M j', strtotime($r['check_out']))) ?><?php if($due):?> <span class="chip" style="font-size:.7rem;background:#fef3e2;color:#9a6a00">today</span><?php endif;?></td>
          <td style="text-align:right"><?= money($r['room_total']) ?></td>
          <td style="text-align:right"><?= $r['charges_total']>0 ? money($r['charges_total']) : '<span class="muted">—</span>' ?></td>
          <td style="text-align:right;color:#14653b"><?= money($r['paid']) ?></td>
          <td style="text-align:right;font-weight:700;color:<?= $r['balance']>0?'#9a6a00':'#14653b' ?>"><?= money($r['balance']) ?></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
              <?php if ($canManage && $r['balance'] > 0.005): ?>
                <form method="post" action="<?= e(url('/admin/billing/'.$r['id'].'/settle')) ?>" onsubmit="return confirm('Record cash payment of <?= e(money($r['balance'])) ?> for <?= e($r['reference']) ?>?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="amount" value="<?= e(number_format($r['balance'],2,'.','')) ?>">
                  <input type="hidden" name="method" value="cash">
                  <input type="hidden" name="filter" value="<?= e($f) ?>">
                  <button class="btn btn-teal btn-sm" type="submit"><?= icon('check','',14) ?> Settle cash</button>
                </form>
              <?php elseif ($canManage && $r['status'] !== 'checked_out'): ?>
                <form method="post" action="<?= e(url('/admin/billing/'.$r['id'].'/checkout')) ?>" onsubmit="return confirm('Check out <?= e($r['guest_name']) ?> (<?= e($r['reference']) ?>)?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="filter" value="<?= e($f) ?>">
                  <button class="btn btn-primary btn-sm" type="submit"><?= icon('door-open','',14) ?> Check out</button>
                </form>
              <?php endif; ?>
              <a class="btn btn-outline btn-sm" href="<?= e(url('/admin/bookings/'.$r['id'])) ?>" title="Open folio"><?= icon('arrow-right','',14) ?></a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<p class="muted" style="font-size:.85rem"><?= icon('check','',14) ?> Add room-service / amenity charges from a guest's booking page. Settling the full balance here records a cash payment; “Check out” is available once the balance is zero.</p>
