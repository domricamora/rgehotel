<?php
use App\Core\Auth;
use App\Models\Folio;
/** @var array $b @var array $payments @var array $charges @var array $folio @var array $categories */
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

    <div class="panel">
      <div class="panel__head">
        <h2>Folio — Room Charges</h2>
        <a class="btn btn-outline btn-sm" href="<?= e(url('/booking/'.$b['reference'].'/billing')) ?>" target="_blank"><?= icon('arrow-right','',14) ?> Guest bill</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Date</th><th>Item</th><th>Category</th><th style="text-align:center">Qty</th><th style="text-align:right">Unit</th><th style="text-align:right">Amount</th><th></th></tr></thead>
          <tbody>
            <?php if (!$charges): ?><tr><td colspan="7" class="muted">No charges posted yet.</td></tr><?php endif; ?>
            <?php foreach ($charges as $c): ?>
            <tr>
              <td class="muted" style="font-size:.82rem"><?= e(date('M j', strtotime($c['charged_at']))) ?></td>
              <td><?= e($c['description']) ?><?php if($c['status']==='paid'):?> <?= badge('paid') ?><?php endif; ?></td>
              <td class="muted" style="font-size:.85rem"><?= e(Folio::categoryLabel($c['category'])) ?></td>
              <td style="text-align:center"><?= rtrim(rtrim(number_format((float)$c['quantity'],2),'0'),'.') ?></td>
              <td style="text-align:right"><?= money($c['unit_price']) ?></td>
              <td style="text-align:right"><?= money($c['amount']) ?></td>
              <td style="text-align:right">
                <?php if ($canManage && $c['status'] !== 'paid'): ?>
                <form method="post" action="<?= e(url('/admin/bookings/'.$b['id'].'/charges/'.$c['id'].'/void')) ?>" onsubmit="return confirm('Void this charge?')">
                  <?= csrf_field() ?><button class="btn btn-outline btn-sm" type="submit" title="Void"><?= icon('x','',14) ?></button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="panel__body">
        <div class="flex" style="justify-content:space-between"><span class="muted">Room total</span><span><?= money($folio['room_total']) ?></span></div>
        <div class="flex mt-1" style="justify-content:space-between"><span class="muted">Charges</span><span><?= money($folio['charges_total']) ?></span></div>
        <div class="flex mt-1" style="justify-content:space-between;font-weight:600"><span>Grand total</span><span><?= money($folio['grand_total']) ?></span></div>
        <div class="flex mt-1" style="justify-content:space-between;color:var(--teal-deep,#14653b)"><span>Paid</span><span><?= money($folio['paid']) ?></span></div>
        <hr style="border:0;border-top:1px solid var(--line);margin:10px 0">
        <div class="flex" style="justify-content:space-between;font-weight:700"><span>Balance due</span><span><?= money($folio['balance']) ?></span></div>
      </div>

      <?php if ($canManage): ?>
      <div class="panel__body" style="border-top:1px solid var(--line)">
        <h3 style="font-size:.95rem;margin:0 0 10px">Post a charge</h3>
        <form method="post" action="<?= e(url('/admin/bookings/'.$b['id'].'/charges')) ?>">
          <?= csrf_field() ?>
          <div class="form-grid">
            <div class="field"><label>Category</label>
              <select name="category"><?php foreach ($categories as $k=>$lbl): ?><option value="<?= e($k) ?>"><?= e($lbl) ?></option><?php endforeach; ?></select>
            </div>
            <div class="field"><label>Date</label><input type="date" name="charged_at" value="<?= e(date('Y-m-d')) ?>"></div>
          </div>
          <div class="field mt-2"><label>Description</label><input type="text" name="description" placeholder="e.g. Club sandwich, in-room dining" required></div>
          <div class="form-grid mt-2">
            <div class="field"><label>Quantity</label><input type="number" name="quantity" value="1" min="0" step="0.01"></div>
            <div class="field"><label>Unit price (PHP)</label><input type="number" name="unit_price" value="0" min="0" step="0.01" required></div>
          </div>
          <button class="btn btn-primary mt-2" type="submit"><?= icon('check') ?> Add charge</button>
        </form>
      </div>

      <?php if ($folio['balance'] > 0): ?>
      <div class="panel__body" style="border-top:1px solid var(--line)">
        <h3 style="font-size:.95rem;margin:0 0 10px">Settle balance (cash / on-site)</h3>
        <form method="post" action="<?= e(url('/admin/bookings/'.$b['id'].'/settle')) ?>" onsubmit="return confirm('Record this payment?')">
          <?= csrf_field() ?>
          <div class="form-grid">
            <div class="field"><label>Amount (PHP)</label><input type="number" name="amount" value="<?= e(number_format($folio['balance'],2,'.','')) ?>" min="0" step="0.01"></div>
            <div class="field"><label>Method</label>
              <select name="method"><?php foreach (['cash','bank','gcash','card','other'] as $m): ?><option value="<?= $m ?>"><?= ucfirst($m) ?></option><?php endforeach; ?></select>
            </div>
          </div>
          <button class="btn btn-teal mt-2" type="submit"><?= icon('check') ?> Record payment</button>
        </form>
      </div>
      <?php endif; ?>
      <?php endif; ?>
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
    <a class="btn btn-outline" href="<?= e(url('/admin/accounting/invoice/'.$b['id'])) ?>" target="_blank"><?= icon('arrow-right') ?> View / print invoice</a>
    <a class="btn btn-outline" href="<?= e(url('/admin/bookings')) ?>">← Back to bookings</a>
  </div>
</div>
