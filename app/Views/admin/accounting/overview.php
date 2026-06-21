<?php /** @var array $summary @var array $byRoom @var array $byProvider @var array $byCategory @var array $daily @var string $from @var string $to */
$maxDaily = 0; foreach ($daily as $d) $maxDaily = max($maxDaily, (float)$d['total']);
?>
<form method="get" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:20px;flex-wrap:wrap">
  <div class="field" style="margin:0"><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div>
  <div class="field" style="margin:0"><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div>
  <button class="btn btn-primary" type="submit"><?= icon('calendar','',16) ?> Apply</button>
  <a class="btn btn-outline" href="<?= e(url('/admin/accounting/reports?from='.$from.'&to='.$to)) ?>"><?= icon('star','',16) ?> Full report</a>
</form>

<div class="stats">
  <div class="stat"><div class="label"><?= icon('percent','',16) ?> Total Income</div><div class="num"><?= money($summary['income']) ?></div><div class="sub">bookings <?= money($summary['booking_revenue']) ?> + other <?= money($summary['other_income']) ?></div></div>
  <div class="stat"><div class="label"><?= icon('tag','',16) ?> Expenses</div><div class="num"><?= money($summary['expenses']) ?></div><div class="sub">refunds <?= money($summary['refunds']) ?></div></div>
  <div class="stat"><div class="label"><?= icon('layout-dashboard','',16) ?> Net Profit</div><div class="num" style="color:<?= $summary['net']>=0?'#14653b':'#9b2226' ?>"><?= money($summary['net']) ?></div><div class="sub">income − refunds − expenses</div></div>
  <div class="stat"><div class="label"><?= icon('clock','',16) ?> Outstanding</div><div class="num"><?= money($summary['outstanding']) ?></div><div class="sub">unpaid confirmed bookings</div></div>
</div>

<div class="panel"><div class="panel__head"><h2>Daily Revenue</h2><span class="muted" style="font-size:.85rem"><?= e($from) ?> – <?= e($to) ?></span></div>
  <div class="panel__body">
    <?php if (!$daily): ?><p class="muted">No paid transactions in this period.</p><?php else: ?>
    <div style="display:flex;align-items:flex-end;gap:6px;height:160px;overflow-x:auto;padding-top:10px">
      <?php foreach ($daily as $d): $h = $maxDaily ? max(4, ($d['total']/$maxDaily)*150) : 4; ?>
        <div style="display:flex;flex-direction:column;align-items:center;gap:4px;min-width:30px" title="<?= e($d['d']) ?>: <?= money($d['total']) ?>">
          <div style="width:22px;height:<?= (int)$h ?>px;background:linear-gradient(180deg,#0e7c86,#0a5b63);border-radius:5px 5px 0 0"></div>
          <span style="font-size:.62rem;color:#7c9197;white-space:nowrap"><?= e(date('M j', strtotime($d['d']))) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="form-grid">
  <div class="panel"><div class="panel__head"><h2>Revenue by Room Type</h2></div><div class="table-wrap"><table>
    <thead><tr><th>Room</th><th style="text-align:right">Revenue</th></tr></thead><tbody>
    <?php if(!$byRoom):?><tr><td colspan="2" class="muted">No data.</td></tr><?php endif;?>
    <?php foreach ($byRoom as $r): ?><tr><td><?= e($r['name']) ?></td><td style="text-align:right"><?= money($r['total']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>

  <div class="panel"><div class="panel__head"><h2>Income by Payment Method</h2></div><div class="table-wrap"><table>
    <thead><tr><th>Provider</th><th>Txns</th><th style="text-align:right">Total</th></tr></thead><tbody>
    <?php if(!$byProvider):?><tr><td colspan="3" class="muted">No data.</td></tr><?php endif;?>
    <?php foreach ($byProvider as $r): ?><tr><td><?= e(ucfirst($r['provider'])) ?></td><td><?= (int)$r['cnt'] ?></td><td style="text-align:right"><?= money($r['total']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
</div>

<div class="panel"><div class="panel__head"><h2>Expenses by Category</h2></div><div class="table-wrap"><table>
  <thead><tr><th>Category</th><th style="text-align:right">Amount</th></tr></thead><tbody>
  <?php if(!$byCategory):?><tr><td colspan="2" class="muted">No expenses recorded.</td></tr><?php endif;?>
  <?php foreach ($byCategory as $r): ?><tr><td><?= e($r['name']) ?></td><td style="text-align:right"><?= money($r['total']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div>
