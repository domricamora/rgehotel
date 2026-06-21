<?php /** @var array $rows @var string $from @var string $to @var string|null $status @var string|null $provider */
$total = 0; foreach ($rows as $r) if ($r['status']==='paid') $total += (float)$r['amount'];
?>
<form method="get" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:20px;flex-wrap:wrap">
  <div class="field" style="margin:0"><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div>
  <div class="field" style="margin:0"><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div>
  <div class="field" style="margin:0"><label>Status</label><select name="status"><option value="">All</option><?php foreach(['paid','pending','failed','refunded'] as $s):?><option <?= $status===$s?'selected':'' ?>><?= $s ?></option><?php endforeach;?></select></div>
  <button class="btn btn-primary" type="submit">Filter</button>
  <a class="btn btn-outline" href="<?= e(url('/admin/accounting/export/ledger?from='.$from.'&to='.$to)) ?>"><?= icon('arrow-right','',16) ?> Export CSV</a>
</form>

<?php if (\App\Core\Auth::can('accounting.manage')): ?>
<div class="panel"><div class="panel__head"><h2>Record a Payment (cash / bank / GCash on arrival)</h2></div><div class="panel__body">
  <form method="post" action="<?= e(url('/admin/accounting/ledger/record')) ?>" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
    <?= csrf_field() ?>
    <div class="field" style="margin:0"><label>Booking ref</label><input name="reference" placeholder="RGE-..." required></div>
    <div class="field" style="margin:0"><label>Amount (₱)</label><input type="number" step="1" name="amount" required></div>
    <div class="field" style="margin:0"><label>Method</label><select name="method"><?php foreach(['cash','bank','gcash','card','other'] as $m):?><option><?= $m ?></option><?php endforeach;?></select></div>
    <div class="field" style="margin:0;flex:1;min-width:160px"><label>Note</label><input name="note"></div>
    <button class="btn btn-primary" type="submit"><?= icon('check','',16) ?> Record</button>
  </form>
</div></div>
<?php endif; ?>

<div class="panel">
  <div class="panel__head"><h2>Payments</h2><span class="badge badge-green">Paid total: <?= money($total) ?></span></div>
  <div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Booking</th><th>Guest</th><th>Provider</th><th>Status</th><th style="text-align:right">Amount</th></tr></thead>
    <tbody>
      <?php if(!$rows):?><tr><td colspan="6" class="muted">No payments in this period.</td></tr><?php endif;?>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td class="muted" style="font-size:.85rem"><?= e(date('M j, Y H:i', strtotime($r['created_at']))) ?></td>
        <td><?php if($r['reference']):?><a href="<?= e(url('/admin/bookings/'.$r['booking_id'])) ?>"><?= e($r['reference']) ?></a><?php else:?>—<?php endif;?></td>
        <td><?= e($r['guest_name'] ?? '—') ?></td>
        <td><?= e(ucfirst($r['provider'])) ?></td>
        <td><?= badge($r['status']) ?></td>
        <td style="text-align:right"><?= money($r['amount']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
