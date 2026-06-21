<?php /** @var array $rows @var array $categories @var float $total @var string $from @var string $to @var bool $canManage @var int|null $categoryId */ ?>
<form method="get" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:20px;flex-wrap:wrap">
  <div class="field" style="margin:0"><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div>
  <div class="field" style="margin:0"><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div>
  <div class="field" style="margin:0"><label>Category</label><select name="category_id"><option value="">All</option><?php foreach($categories as $c):?><option value="<?= $c['id'] ?>" <?= $categoryId==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach;?></select></div>
  <button class="btn btn-primary" type="submit">Filter</button>
  <a class="btn btn-outline" href="<?= e(url('/admin/accounting/export/expenses?from='.$from.'&to='.$to)) ?>"><?= icon('arrow-right','',16) ?> Export CSV</a>
</form>

<?php if ($canManage): ?>
<div class="panel"><div class="panel__head"><h2>Record an Expense</h2></div><div class="panel__body">
  <form method="post" action="<?= e(url('/admin/accounting/expenses/new')) ?>">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="field"><label>Date</label><input type="date" name="expense_date" value="<?= e(date('Y-m-d')) ?>" required></div>
      <div class="field"><label>Category</label><select name="category_id"><?php foreach($categories as $c):?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach;?></select></div>
      <div class="field full"><label>Description</label><input name="description" required></div>
      <div class="field"><label>Vendor</label><input name="vendor"></div>
      <div class="field"><label>Amount (₱)</label><input type="number" step="0.01" name="amount" required></div>
      <div class="field"><label>Method</label><select name="payment_method"><?php foreach(['cash','bank','gcash','card','other'] as $m):?><option><?= $m ?></option><?php endforeach;?></select></div>
      <div class="field"><label>Reference</label><input name="reference"></div>
      <div class="field full"><label>Notes</label><input name="notes"></div>
    </div>
    <button class="btn btn-primary" type="submit"><?= icon('check','',16) ?> Save Expense</button>
  </form>
</div></div>
<?php endif; ?>

<div class="panel">
  <div class="panel__head"><h2>Expenses</h2><span class="badge badge-red">Total: <?= money($total) ?></span></div>
  <div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Vendor</th><th>Method</th><th style="text-align:right">Amount</th><?php if($canManage):?><th></th><?php endif;?></tr></thead>
    <tbody>
      <?php if(!$rows):?><tr><td colspan="7" class="muted">No expenses in this period.</td></tr><?php endif;?>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td class="muted" style="font-size:.85rem"><?= e(date('M j, Y', strtotime($r['expense_date']))) ?></td>
        <td><?= e($r['category_name'] ?? '—') ?></td>
        <td><?= e($r['description']) ?><?php if($r['notes']):?><div class="muted" style="font-size:.8rem"><?= e($r['notes']) ?></div><?php endif;?></td>
        <td><?= e($r['vendor'] ?: '—') ?></td>
        <td><?= e($r['payment_method'] ?: '—') ?></td>
        <td style="text-align:right"><?= money($r['amount']) ?></td>
        <?php if($canManage):?><td><form method="post" action="<?= e(url('/admin/accounting/expenses/'.$r['id'].'/delete')) ?>" onsubmit="return confirm('Delete this expense?')"><?= csrf_field() ?><button class="btn btn-danger btn-sm"><?= icon('x','',14) ?></button></form></td><?php endif;?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
