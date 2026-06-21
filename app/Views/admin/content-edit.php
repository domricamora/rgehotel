<?php /** @var string $entity @var array $row @var string $label @var string $id */
$v = fn($k, $d = '') => e($row[$k] ?? $d);
$chk = fn($k, $def=0) => (($row[$k] ?? $def)) ? 'checked' : '';
?>
<form method="post" action="<?= e(url('/admin/'.$entity.'/'.$id)) ?>">
  <?= csrf_field() ?>
  <div class="panel"><div class="panel__head"><h2><?= $id==='new'?'New':'Edit' ?> <?= e($label) ?></h2></div><div class="panel__body">
    <div class="form-grid">

      <?php if ($entity === 'services'): ?>
        <div class="field full"><label>Name</label><input name="name" value="<?= $v('name') ?>" required></div>
        <div class="field"><label>Category</label><select name="category"><?php foreach(['island_hopping','tour','diving','watersport','transfer','car','spa','other'] as $c):?><option value="<?= $c ?>" <?= ($row['category']??'')===$c?'selected':'' ?>><?= ucwords(str_replace('_',' ',$c)) ?></option><?php endforeach;?></select></div>
        <div class="field"><label>Duration</label><input name="duration" value="<?= $v('duration') ?>"></div>
        <div class="field"><label>Price (₱)</label><input type="number" step="1" name="price" value="<?= e((int)($row['price']??0)) ?>"></div>
        <div class="field"><label>Price unit</label><input name="price_unit" value="<?= $v('price_unit','per person') ?>"></div>
        <div class="field full"><label>Summary</label><input name="summary" value="<?= $v('summary') ?>"></div>
        <div class="field full"><label>Description</label><textarea name="description" rows="4"><?= $v('description') ?></textarea></div>
        <div class="field full"><label>Highlights (one per line)</label><textarea name="highlights" rows="3"><?= $v('highlights') ?></textarea></div>
        <div class="field full"><label>SEO description</label><input name="meta_description" value="<?= $v('meta_description') ?>"></div>

      <?php elseif ($entity === 'packages'): ?>
        <div class="field full"><label>Name</label><input name="name" value="<?= $v('name') ?>" required></div>
        <div class="field"><label>Price (₱)</label><input type="number" name="price" value="<?= e((int)($row['price']??0)) ?>"></div>
        <div class="field"><label>Original price (₱)</label><input type="number" name="original_price" value="<?= e((int)($row['original_price']??0)) ?>"></div>
        <div class="field"><label>Nights</label><input type="number" name="nights" value="<?= e($row['nights']??'') ?>"></div>
        <div class="field"><label>Pax</label><input type="number" name="pax" value="<?= e($row['pax']??'') ?>"></div>
        <div class="field full"><label>Summary</label><input name="summary" value="<?= $v('summary') ?>"></div>
        <div class="field full"><label>Description</label><textarea name="description" rows="4"><?= $v('description') ?></textarea></div>
        <div class="field full"><label>Inclusions (one per line)</label><textarea name="inclusions" rows="4"><?= $v('inclusions') ?></textarea></div>
        <div class="field full"><label>SEO description</label><input name="meta_description" value="<?= $v('meta_description') ?>"></div>

      <?php else: /* offers */ ?>
        <div class="field full"><label>Title</label><input name="title" value="<?= $v('title') ?>" required></div>
        <div class="field full"><label>Subtitle</label><input name="subtitle" value="<?= $v('subtitle') ?>"></div>
        <div class="field"><label>Discount type</label><select name="discount_type"><option value="percent" <?= ($row['discount_type']??'')==='percent'?'selected':'' ?>>Percent (%)</option><option value="fixed" <?= ($row['discount_type']??'')==='fixed'?'selected':'' ?>>Fixed (₱)</option></select></div>
        <div class="field"><label>Discount value</label><input type="number" name="discount_value" value="<?= e((int)($row['discount_value']??0)) ?>"></div>
        <div class="field"><label>Promo code</label><input name="code" value="<?= $v('code') ?>"></div>
        <div class="field"><label>&nbsp;</label></div>
        <div class="field"><label>Starts</label><input type="date" name="starts_at" value="<?= e(substr($row['starts_at']??'',0,10)) ?>"></div>
        <div class="field"><label>Ends</label><input type="date" name="ends_at" value="<?= e(substr($row['ends_at']??'',0,10)) ?>"></div>
        <div class="field full"><label>Description</label><textarea name="description" rows="3"><?= $v('description') ?></textarea></div>
      <?php endif; ?>

      <div class="field"><label>Sort order</label><input type="number" name="sort_order" value="<?= e($row['sort_order']??0) ?>"></div>
      <div class="field"><label>&nbsp;</label>
        <div class="checkbox"><input type="checkbox" name="is_published" value="1" <?= $chk('is_published',1) ?>> <span>Published</span></div>
        <div class="checkbox"><input type="checkbox" name="is_featured" value="1" <?= $chk('is_featured') ?>> <span>Featured</span></div>
      </div>
    </div>
  </div></div>
  <div class="row-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save</button><a class="btn btn-outline" href="<?= e(url('/admin/'.$entity)) ?>">Cancel</a></div>
</form>
