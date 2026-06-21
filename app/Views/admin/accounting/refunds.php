<?php /** @var array $rows @var float $total @var string $from @var string $to @var bool $canManage */ ?>
<form method="get" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:20px;flex-wrap:wrap">
  <div class="field" style="margin:0"><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div>
  <div class="field" style="margin:0"><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div>
  <button class="btn btn-primary" type="submit">Filter</button>
</form>

<?php if ($canManage): ?>
<div class="panel"><div class="panel__head"><h2>Record a Refund</h2></div><div class="panel__body">
  <form method="post" action="<?= e(url('/admin/accounting/refunds')) ?>" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
    <?= csrf_field() ?>
    <div class="field" style="margin:0"><label>Booking ref</label><input name="reference" placeholder="RGE-..." required></div>
    <div class="field" style="margin:0"><label>Amount (₱)</label><input type="number" step="0.01" name="amount" required></div>
    <div class="field" style="margin:0"><label>Method</label><select name="method"><?php foreach(['bank','gcash','cash','card','other'] as $m):?><option><?= $m ?></option><?php endforeach;?></select></div>
    <div class="field" style="margin:0;flex:1;min-width:160px"><label>Reason</label><input name="reason"></div>
    <button class="btn btn-primary" type="submit"><?= icon('check','',16) ?> Record refund</button>
  </form>
  <p class="muted" style="font-size:.82rem;margin-top:8px">Recording a refund marks the booking as cancelled/refunded.</p>
</div></div>
<?php endif; ?>

<div class="panel">
  <div class="panel__head"><h2>Refunds</h2><span class="badge badge-amber">Total: <?= money($total) ?></span></div>
  <div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Booking</th><th>Guest</th><th>Reason</th><th>Method</th><th>By</th><th style="text-align:right">Amount</th></tr></thead>
    <tbody>
      <?php if(!$rows):?><tr><td colspan="7" class="muted">No refunds in this period.</td></tr><?php endif;?>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td class="muted" style="font-size:.85rem"><?= e(date('M j, Y', strtotime($r['created_at']))) ?></td>
        <td><?php if($r['reference']):?><a href="<?= e(url('/admin/bookings/'.$r['booking_id'])) ?>"><?= e($r['reference']) ?></a><?php else:?>—<?php endif;?></td>
        <td><?= e($r['guest_name'] ?? '—') ?></td>
        <td><?= e($r['reason'] ?: '—') ?></td>
        <td><?= e($r['method'] ?: '—') ?></td>
        <td><?= e($r['refunder'] ?? '—') ?></td>
        <td style="text-align:right">−<?= money($r['amount']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
