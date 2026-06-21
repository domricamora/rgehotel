<?php /** @var array $room @var array $photos */ ?>
<form method="post" action="<?= e(url('/admin/rooms/'.$room['id'])) ?>">
  <?= csrf_field() ?>
  <div class="panel">
    <div class="panel__head"><h2><?= e($room['name']) ?></h2><a class="btn btn-outline btn-sm" href="<?= e(url('/accommodations/'.$room['slug'])) ?>" target="_blank">View live <?= icon('arrow-right','',14) ?></a></div>
    <div class="panel__body">
      <div class="form-grid">
        <div class="field full"><label>Name</label><input name="name" value="<?= e($room['name']) ?>" required></div>
        <div class="field full"><label>Summary (short tagline)</label><input name="summary" value="<?= e($room['summary']) ?>"></div>
        <div class="field full"><label>Description</label><textarea name="description" rows="5"><?= e($room['description']) ?></textarea></div>
        <div class="field"><label>Base price / night (₱)</label><input type="number" step="1" name="base_price" value="<?= e((int)$room['base_price']) ?>"></div>
        <div class="field"><label>Weekend price (₱)</label><input type="number" step="1" name="weekend_price" value="<?= e((int)$room['weekend_price']) ?>"></div>
        <div class="field"><label>Max occupancy</label><input type="number" name="max_occupancy" value="<?= e($room['max_occupancy']) ?>"></div>
        <div class="field"><label>Total units</label><input type="number" name="total_units" value="<?= e($room['total_units']) ?>"></div>
        <div class="field"><label>Beds</label><input name="beds" value="<?= e($room['beds']) ?>"></div>
        <div class="field"><label>Size (m²)</label><input type="number" name="size_sqm" value="<?= e($room['size_sqm']) ?>"></div>
        <div class="field"><label>View</label><input name="view" value="<?= e($room['view']) ?>"></div>
        <div class="field"><label>&nbsp;</label>
          <div class="checkbox"><input type="checkbox" name="is_published" value="1" <?= $room['is_published']?'checked':'' ?>> <span>Published</span></div>
          <div class="checkbox"><input type="checkbox" name="is_featured" value="1" <?= $room['is_featured']?'checked':'' ?>> <span>Featured on home</span></div>
        </div>
        <div class="field full"><label>SEO title</label><input name="meta_title" value="<?= e($room['meta_title']) ?>"></div>
        <div class="field full"><label>SEO description</label><textarea name="meta_description" rows="2"><?= e($room['meta_description']) ?></textarea></div>
      </div>
    </div>
  </div>
  <?php if ($photos): ?>
  <div class="panel"><div class="panel__head"><h2>Photos</h2></div><div class="panel__body"><div class="pill-row">
    <?php foreach ($photos as $ph): ?><img src="<?= e(img_url($ph['filename'],'thumb')) ?>" alt="" style="width:120px;height:90px;object-fit:cover;border-radius:10px;border:<?= $ph['is_cover']?'2px solid var(--teal)':'1px solid var(--line)' ?>"><?php endforeach; ?>
  </div><p class="muted mt-2" style="font-size:.82rem">Photo upload management coming in the next iteration. Bordered image is the cover.</p></div></div>
  <?php endif; ?>
  <div class="row-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save changes</button><a class="btn btn-outline" href="<?= e(url('/admin/rooms')) ?>">Cancel</a></div>
</form>
