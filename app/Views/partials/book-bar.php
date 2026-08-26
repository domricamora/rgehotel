<?php
/** Search/booking bar. @var string|null $check_in @var string|null $check_out */
$ci = $check_in ?? '';
$co = $check_out ?? '';
$selectedGuests = max(1, min(10, (int) ($guests ?? 1)));
$selectedRooms = max(1, min(5, (int) ($rooms ?? 1)));
?>
<form class="book-bar reveal" method="get" action="<?= e(url('/accommodations')) ?>">
  <div class="field">
    <label for="bb-in">Check-in</label>
    <input type="date" id="bb-in" name="check_in" value="<?= e($ci) ?>" required>
  </div>
  <div class="field">
    <label for="bb-out">Check-out</label>
    <input type="date" id="bb-out" name="check_out" value="<?= e($co) ?>" required>
  </div>
  <div class="field">
    <label for="bb-guests">Guests</label>
    <select id="bb-guests" name="guests">
      <?php for ($i = 1; $i <= 10; $i++): ?><option value="<?= $i ?>"<?= $i === $selectedGuests ? ' selected' : '' ?>><?= $i ?> guest<?= $i > 1 ? 's' : '' ?></option><?php endfor; ?>
    </select>
  </div>
  <div class="field">
    <label for="bb-rooms">Rooms</label>
    <select id="bb-rooms" name="rooms">
      <?php for ($i = 1; $i <= 5; $i++): ?><option value="<?= $i ?>"<?= $i === $selectedRooms ? ' selected' : '' ?>><?= $i ?> room<?= $i > 1 ? 's' : '' ?></option><?php endfor; ?>
    </select>
  </div>
  <div class="field field--submit">
    <button class="btn btn-primary btn-block" type="submit"><?= icon('calendar') ?> Check Availability</button>
  </div>
</form>
