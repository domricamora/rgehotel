<?php /** @var array $room @var array $photos @var string $id */
$isNew = $id === 'new';
$v = fn($k, $d = '') => e($room[$k] ?? $d);
?>
<form method="post" action="<?= e(url('/admin/rooms/'.$id)) ?>">
  <?= csrf_field() ?>
  <div class="panel">
    <div class="panel__head">
      <h2><?= $isNew ? 'New Room Type' : e($room['name']) ?></h2>
      <?php if (!$isNew): ?><a class="btn btn-outline btn-sm" href="<?= e(url('/accommodations/'.$room['slug'])) ?>" target="_blank">View live <?= icon('arrow-right','',14) ?></a><?php endif; ?>
    </div>
    <div class="panel__body">
      <div class="form-grid">
        <div class="field full"><label>Name</label><input name="name" value="<?= $v('name') ?>" required></div>
        <?php if ($isNew): ?>
        <div class="field full"><label>Slug (URL) — leave blank to auto-generate</label><input name="slug" value=""></div>
        <?php endif; ?>
        <div class="field full"><label>Summary (short tagline)</label><input name="summary" value="<?= $v('summary') ?>"></div>
        <div class="field full"><label>Description</label><textarea name="description" rows="5"><?= $v('description') ?></textarea></div>
        <div class="field"><label>Base price / night (₱)</label><input type="number" step="1" name="base_price" value="<?= e((int)($room['base_price'] ?? 0)) ?>"></div>
        <div class="field"><label>Weekend price (₱)</label><input type="number" step="1" name="weekend_price" value="<?= e((int)($room['weekend_price'] ?? 0)) ?>"></div>
        <div class="field"><label>Max occupancy</label><input type="number" name="max_occupancy" value="<?= e($room['max_occupancy'] ?? 2) ?>"></div>
        <div class="field"><label>Total units</label><input type="number" name="total_units" value="<?= e($room['total_units'] ?? 1) ?>"></div>
        <div class="field"><label>Adults</label><input type="number" name="adults" value="<?= e($room['adults'] ?? 2) ?>"></div>
        <div class="field"><label>Children</label><input type="number" name="children" value="<?= e($room['children'] ?? 0) ?>"></div>
        <div class="field"><label>Beds</label><input name="beds" value="<?= $v('beds') ?>"></div>
        <div class="field"><label>Size (m²)</label><input type="number" name="size_sqm" value="<?= e($room['size_sqm'] ?? '') ?>"></div>
        <div class="field"><label>View</label><input name="view" value="<?= $v('view') ?>"></div>
        <div class="field"><label>Sort order</label><input type="number" name="sort_order" value="<?= e($room['sort_order'] ?? 0) ?>"></div>
        <div class="field"><label>&nbsp;</label>
          <div class="checkbox"><input type="checkbox" name="is_published" value="1" <?= ($room['is_published'] ?? 1)?'checked':'' ?>> <span>Published</span></div>
          <div class="checkbox"><input type="checkbox" name="is_featured" value="1" <?= !empty($room['is_featured'])?'checked':'' ?>> <span>Featured on home</span></div>
        </div>
        <div class="field full"><label>SEO title</label><input name="meta_title" value="<?= $v('meta_title') ?>"></div>
        <div class="field full"><label>SEO description</label><textarea name="meta_description" rows="2"><?= $v('meta_description') ?></textarea></div>
      </div>
    </div>
  </div>
  <div class="row-actions">
    <button class="btn btn-primary" type="submit"><?= icon('check') ?> <?= $isNew ? 'Create room type' : 'Save changes' ?></button>
    <a class="btn btn-outline" href="<?= e(url('/admin/rooms')) ?>">Cancel</a>
  </div>
</form>

<?php if (!$isNew): ?>
<div class="panel">
  <div class="panel__head"><h2>Photos</h2></div>
  <div class="panel__body">
    <?php if ($photos): ?>
    <div class="pill-row" style="flex-wrap:wrap;gap:14px">
      <?php foreach ($photos as $ph): ?>
        <div style="width:150px">
          <img src="<?= e(img_url($ph['filename'],'thumb')) ?>" alt="" style="width:150px;height:110px;object-fit:cover;border-radius:0;border:<?= $ph['is_cover']?'3px solid var(--teal)':'1px solid var(--line)' ?>">
          <div class="row-actions" style="margin-top:6px;gap:6px">
            <?php if (!$ph['is_cover']): ?>
            <form method="post" action="<?= e(url('/admin/rooms/'.$room['id'].'/photos/'.$ph['id'].'/cover')) ?>" style="display:inline">
              <?= csrf_field() ?><button class="btn btn-outline btn-sm" title="Set as cover"><?= icon('star','',13) ?> Cover</button>
            </form>
            <?php else: ?><span class="badge badge-blue">Cover</span><?php endif; ?>
            <form method="post" action="<?= e(url('/admin/rooms/'.$room['id'].'/photos/'.$ph['id'].'/delete')) ?>" style="display:inline" onsubmit="return confirm('Delete this photo?')">
              <?= csrf_field() ?><button class="btn btn-outline btn-sm" style="color:var(--red,#c0392b)"><?= icon('x','',13) ?></button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <p class="muted">No photos yet. Upload one or more below — the first becomes the cover.</p>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/admin/rooms/'.$room['id'].'/photos')) ?>" enctype="multipart/form-data" class="mt-2" style="margin-top:16px">
      <?= csrf_field() ?>
      <div class="field"><label>Add photos (JPG / PNG / WebP — multiple allowed)</label>
        <input type="file" name="photos[]" accept="image/*" multiple required>
      </div>
      <button class="btn btn-primary btn-sm" type="submit" style="margin-top:8px"><?= icon('check') ?> Upload photos</button>
    </form>
  </div>
</div>
<?php endif; ?>
