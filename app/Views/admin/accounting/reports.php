<?php /** @var array $summary @var array $byRoom @var array $byProvider @var array $byCategory @var string $from @var string $to */
$s = $summary;
?>
<form method="get" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:20px;flex-wrap:wrap">
  <div class="field" style="margin:0"><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div>
  <div class="field" style="margin:0"><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div>
  <button class="btn btn-primary" type="submit"><?= icon('calendar','',16) ?> Generate</button>
  <a class="btn btn-outline" href="<?= e(url('/admin/accounting/export/pnl?from='.$from.'&to='.$to)) ?>"><?= icon('arrow-right','',16) ?> Export P&L (CSV)</a>
</form>

<div class="panel">
  <div class="panel__head"><h2>Profit &amp; Loss</h2><span class="muted" style="font-size:.85rem"><?= e(date('M j, Y', strtotime($from))) ?> – <?= e(date('M j, Y', strtotime($to))) ?></span></div>
  <div class="panel__body">
    <table>
      <tbody>
        <tr><td>Booking revenue</td><td style="text-align:right"><?= money($s['booking_revenue']) ?></td></tr>
        <tr><td>Other income</td><td style="text-align:right"><?= money($s['other_income']) ?></td></tr>
        <tr><td><strong>Total income</strong></td><td style="text-align:right"><strong><?= money($s['income']) ?></strong></td></tr>
        <tr><td>Less: refunds</td><td style="text-align:right;color:#9b2226">−<?= money($s['refunds']) ?></td></tr>
        <tr><td>Less: expenses</td><td style="text-align:right;color:#9b2226">−<?= money($s['expenses']) ?></td></tr>
        <tr style="border-top:2px solid #0b2229"><td><strong>Net profit</strong></td><td style="text-align:right;font-size:1.15rem;font-weight:700;color:<?= $s['net']>=0?'#14653b':'#9b2226' ?>"><?= money($s['net']) ?></td></tr>
      </tbody>
    </table>
  </div>
</div>

<div class="panel">
  <div class="panel__head"><h2>VAT Summary (<?= e($s['vat']['rate']) ?>% · VAT-inclusive)</h2></div>
  <div class="panel__body"><table><tbody>
    <tr><td>Gross income (VAT-inclusive)</td><td style="text-align:right"><?= money($s['vat']['gross']) ?></td></tr>
    <tr><td>VATable sales (net of VAT)</td><td style="text-align:right"><?= money($s['vat']['net']) ?></td></tr>
    <tr><td>Output VAT (<?= e($s['vat']['rate']) ?>%)</td><td style="text-align:right"><?= money($s['vat']['vat']) ?></td></tr>
  </tbody></table>
  <p class="muted" style="font-size:.8rem;margin-top:10px">Indicative figures for bookkeeping. Confirm with your accountant for BIR filing.</p>
  </div>
</div>

<div class="form-grid">
  <div class="panel"><div class="panel__head"><h2>Revenue by Room Type</h2></div><div class="table-wrap"><table>
    <thead><tr><th>Room</th><th style="text-align:right">Revenue</th></tr></thead><tbody>
    <?php if(!$byRoom):?><tr><td colspan="2" class="muted">No data.</td></tr><?php endif;?>
    <?php foreach ($byRoom as $r): ?><tr><td><?= e($r['name']) ?></td><td style="text-align:right"><?= money($r['total']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  <div class="panel"><div class="panel__head"><h2>Expenses by Category</h2></div><div class="table-wrap"><table>
    <thead><tr><th>Category</th><th style="text-align:right">Amount</th></tr></thead><tbody>
    <?php if(!$byCategory):?><tr><td colspan="2" class="muted">No data.</td></tr><?php endif;?>
    <?php foreach ($byCategory as $r): ?><tr><td><?= e($r['name']) ?></td><td style="text-align:right"><?= money($r['total']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
</div>
