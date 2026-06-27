<?php /** @var array $rooms @var string $check_in @var string $check_out */ ?>
<form method="post" action="<?= e(url('/admin/bookings/new')) ?>">
  <?= csrf_field() ?>
  <div class="panel">
    <div class="panel__head"><h2>New Walk-in / Phone Booking</h2></div>
    <div class="panel__body">
      <div class="form-grid">
        <div class="field full"><label>Room type</label>
          <select name="room_type_id" id="rt" required>
            <option value="">— Select a room —</option>
            <?php foreach ($rooms as $r): ?>
              <option value="<?= (int)$r['id'] ?>" data-price="<?= (float)$r['base_price'] ?>" data-max="<?= (int)$r['max_occupancy'] ?>">
                <?= e($r['name']) ?> — <?= money($r['base_price']) ?>/night (sleeps <?= (int)$r['max_occupancy'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>Check-in</label><input type="date" name="check_in" id="ci" value="<?= e($check_in) ?>" required></div>
        <div class="field"><label>Check-out</label><input type="date" name="check_out" id="co" value="<?= e($check_out) ?>" required></div>
        <div class="field"><label>Rooms</label><input type="number" name="rooms_count" id="rc" value="1" min="1"></div>
        <div class="field"><label>Adults</label><input type="number" name="adults" value="2" min="1"></div>
        <div class="field"><label>Children</label><input type="number" name="children" value="0" min="0"></div>
        <div class="field"><label>Offer code (optional)</label><input name="offer_code" placeholder="e.g. EARLYBIRD"></div>
        <div class="field"><label>Booking status</label>
          <select name="status">
            <option value="confirmed">Confirmed</option>
            <option value="checked_in">Checked in</option>
            <option value="pending">Pending</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel__head"><h2>Guest details</h2></div>
    <div class="panel__body">
      <div class="form-grid">
        <div class="field full"><label>Guest name</label><input name="guest_name" required></div>
        <div class="field"><label>Email (optional)</label><input type="email" name="guest_email"></div>
        <div class="field"><label>Phone (optional)</label><input name="guest_phone"></div>
        <div class="field"><label>Country (optional)</label><input name="guest_country"></div>
        <div class="field full"><label>Special requests</label><textarea name="special_requests" rows="2"></textarea></div>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel__head">
      <h2>Payment</h2>
      <div class="muted" style="font-size:.95rem">Estimated total: <strong id="est">—</strong></div>
    </div>
    <div class="panel__body">
      <div class="form-grid">
        <div class="field full">
          <div class="checkbox"><input type="checkbox" name="mark_paid" id="mp" value="1"> <span>Mark as paid now (records an on-site payment)</span></div>
        </div>
        <div class="field"><label>Payment method</label>
          <select name="payment_method">
            <option value="cash">Cash</option>
            <option value="card">Card</option>
            <option value="gcash">GCash</option>
            <option value="bank">Bank transfer</option>
          </select>
        </div>
        <div class="field"><label>Amount received (₱) — blank = full total</label><input type="number" step="1" name="amount_paid" placeholder="full total"></div>
      </div>
    </div>
  </div>

  <div class="row-actions">
    <button class="btn btn-primary" type="submit"><?= icon('check') ?> Create booking</button>
    <a class="btn btn-outline" href="<?= e(url('/admin/bookings')) ?>">Cancel</a>
  </div>
</form>

<script>
(function(){
  var rt=document.getElementById('rt'),ci=document.getElementById('ci'),co=document.getElementById('co'),rc=document.getElementById('rc'),est=document.getElementById('est');
  function nights(){var a=new Date(ci.value),b=new Date(co.value);var d=Math.round((b-a)/86400000);return d>0?d:0;}
  function calc(){
    var opt=rt.options[rt.selectedIndex],price=opt?parseFloat(opt.getAttribute('data-price')||0):0;
    var n=nights(),rooms=parseInt(rc.value||1,10);
    var total=price*n*rooms;
    est.textContent = (n&&price)? '₱'+total.toLocaleString()+' ('+n+' night(s) × '+rooms+' room(s))' : '—';
  }
  [rt,ci,co,rc].forEach(function(el){el&&el.addEventListener('input',calc);});
  calc();
})();
</script>
