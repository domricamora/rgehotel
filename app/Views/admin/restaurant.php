<?php /** @var array $cats @var bool $published */ ?>
<div class="panel">
  <div class="panel__head"><h2>Restaurant Visibility</h2></div>
  <div class="panel__body">
    <div class="alert <?= $published ? 'alert-success' : 'alert-info' ?>">
      The restaurant page is currently <strong><?= $published ? 'PUBLISHED' : 'UNPUBLISHED' ?></strong> on the live site.
      <?= $published ? '' : 'It returns a “coming soon” page to visitors.' ?>
    </div>
    <form method="post" action="<?= e(url('/admin/restaurant/publish')) ?>" class="row-actions">
      <?= csrf_field() ?>
      <label class="checkbox"><input type="checkbox" name="published" value="1" <?= $published?'checked':'' ?>> <span>Publish restaurant page on the live website</span></label>
      <button class="btn btn-primary btn-sm" type="submit"><?= icon('check') ?> Update</button>
    </form>
  </div>
</div>

<?php foreach ($cats as $cat): ?>
<div class="panel">
  <div class="panel__head"><h2><?= e($cat['name']) ?></h2></div>
  <div class="panel__body">
    <?php foreach ($cat['items'] as $it): ?>
    <form method="post" action="<?= e(url('/admin/restaurant/'.$it['id'])) ?>" style="display:flex;gap:10px;align-items:flex-start;padding:10px 0;border-bottom:1px solid var(--line)">
      <?= csrf_field() ?>
      <div style="flex:1">
        <input name="name" value="<?= e($it['name']) ?>" style="border:1px solid var(--line);border-radius:0;padding:7px 10px;width:100%">
        <input name="description" value="<?= e($it['description']) ?>" placeholder="description" style="border:1px solid var(--line);border-radius:0;padding:6px 10px;width:100%;margin-top:5px;font-size:.82rem;color:var(--muted)">
      </div>
      <input type="number" name="price" value="<?= e((int)$it['price']) ?>" style="border:1px solid var(--line);border-radius:0;padding:7px 10px;width:96px">
      <label class="checkbox" style="white-space:nowrap;padding-top:8px"><input type="checkbox" name="is_available" value="1" <?= $it['is_available']?'checked':'' ?>> <span style="font-size:.82rem">Available</span></label>
      <button class="btn btn-primary btn-sm" type="submit" style="margin-top:2px"><?= icon('check','',14) ?> Save</button>
    </form>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>
