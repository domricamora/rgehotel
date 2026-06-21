<?php
use App\Models\Setting;
use App\Models\Folio;
/** @var array $b @var array $payments @var array $vat @var array $charges @var array $folio */
$grandTotal = $folio['grand_total'];
$paid = $folio['paid'];
$balance = $folio['balance'];
?>
<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:28px">
  <div>
    <img src="<?= e(img_url('general/logo','full')) ?>" alt="RGE Hotel" style="height:60px;margin-bottom:8px">
    <div style="font-size:.85rem;color:#5e564a">
      <?= e(Setting::get('contact_address','Palompon, Leyte, Philippines')) ?><br>
      <?= e(Setting::get('contact_email','info@rgehotel.com')) ?>
      <?php if(Setting::get('business_tin')):?><br>TIN: <?= e(Setting::get('business_tin')) ?><?php endif;?>
    </div>
  </div>
  <div style="text-align:right">
    <h1 style="font-size:1.6rem;margin:0">INVOICE</h1>
    <div style="font-size:.9rem;color:#5e564a;margin-top:6px"><strong><?= e($b['reference']) ?></strong><br><?= e(date('M j, Y', strtotime($b['created_at']))) ?></div>
    <div style="margin-top:8px;display:inline-block;padding:4px 12px;border-radius:0;font-size:.78rem;font-weight:600;background:<?= $balance<=0?'#e8f6ee':'#fef3e2' ?>;color:<?= $balance<=0?'#14653b':'#9a6a00' ?>"><?= $balance<=0?'PAID':'BALANCE DUE' ?></div>
  </div>
</div>

<div style="display:flex;justify-content:space-between;gap:24px;margin-bottom:24px;font-size:.9rem">
  <div><div style="color:#948a7a;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em">Billed to</div>
    <strong><?= e($b['guest_name']) ?></strong><br><?= e($b['guest_email']) ?><?php if($b['guest_phone']):?><br><?= e($b['guest_phone']) ?><?php endif;?><?php if($b['guest_country']):?><br><?= e($b['guest_country']) ?><?php endif;?>
  </div>
  <div style="text-align:right"><div style="color:#948a7a;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em">Stay</div>
    <?= e(date('D, M j, Y', strtotime($b['check_in']))) ?> →<br><?= e(date('D, M j, Y', strtotime($b['check_out']))) ?><br><?= (int)$b['nights'] ?> night(s) · <?= (int)$b['rooms_count'] ?> room(s)
  </div>
</div>

<table style="width:100%;border-collapse:collapse;font-size:.92rem;margin-bottom:20px">
  <thead><tr style="border-bottom:2px solid #2b2620"><th style="text-align:left;padding:10px 0">Description</th><th style="text-align:center;padding:10px">Qty</th><th style="text-align:right;padding:10px 0">Amount</th></tr></thead>
  <tbody>
    <tr style="border-bottom:1px solid #e7e0d2"><td style="padding:12px 0"><?= e($b['room_name']) ?> — <?= (int)$b['nights'] ?> night(s)<?php if($b['offer_code']):?> <span style="color:#948a7a">(<?= e($b['offer_code']) ?>)</span><?php endif;?></td><td style="text-align:center"><?= (int)$b['rooms_count'] ?></td><td style="text-align:right"><?= money($b['subtotal']) ?></td></tr>
    <?php if($b['discount']>0):?><tr style="border-bottom:1px solid #e7e0d2"><td style="padding:12px 0;color:#9b2226">Discount</td><td></td><td style="text-align:right;color:#9b2226">−<?= money($b['discount']) ?></td></tr><?php endif;?>
    <?php if($charges):?>
      <tr><td colspan="3" style="padding:14px 0 4px;font-size:.78rem;text-transform:uppercase;letter-spacing:.05em;color:#948a7a">In-house charges</td></tr>
      <?php foreach($charges as $c):?>
      <tr style="border-bottom:1px solid #e7e0d2"><td style="padding:10px 0"><?= e($c['description']) ?> <span style="color:#948a7a">(<?= e(Folio::categoryLabel($c['category'])) ?>)</span></td><td style="text-align:center"><?= rtrim(rtrim(number_format((float)$c['quantity'],2),'0'),'.') ?></td><td style="text-align:right"><?= money($c['amount']) ?></td></tr>
      <?php endforeach;?>
    <?php endif;?>
  </tbody>
</table>

<div style="display:flex;justify-content:flex-end">
  <table style="font-size:.92rem;min-width:280px">
    <tbody>
      <?php if($folio['charges_total']>0):?>
      <tr><td style="padding:5px 0;color:#5e564a">Room</td><td style="text-align:right"><?= money($folio['room_total']) ?></td></tr>
      <tr><td style="padding:5px 0;color:#5e564a">In-house charges</td><td style="text-align:right"><?= money($folio['charges_total']) ?></td></tr>
      <?php endif;?>
      <tr><td style="padding:5px 0;color:#5e564a">VATable sales (net)</td><td style="text-align:right"><?= money($vat['net']) ?></td></tr>
      <tr><td style="padding:5px 0;color:#5e564a">VAT (<?= e($vat['rate']) ?>%)</td><td style="text-align:right"><?= money($vat['vat']) ?></td></tr>
      <tr style="border-top:2px solid #2b2620"><td style="padding:8px 0;font-weight:700;font-size:1.05rem">Total</td><td style="text-align:right;font-weight:700;font-size:1.05rem"><?= money($grandTotal) ?></td></tr>
      <tr><td style="padding:5px 0;color:#14653b">Paid</td><td style="text-align:right;color:#14653b"><?= money($paid) ?></td></tr>
      <?php if($balance>0):?><tr><td style="padding:5px 0;font-weight:700;color:#9a6a00">Balance due</td><td style="text-align:right;font-weight:700;color:#9a6a00"><?= money($balance) ?></td></tr><?php endif;?>
    </tbody>
  </table>
</div>

<p style="margin-top:40px;font-size:.82rem;color:#948a7a;text-align:center">Thank you for choosing RGE Hotel — your island escape at the gateway to Kalanggaman Island. · This is a system-generated invoice.</p>
