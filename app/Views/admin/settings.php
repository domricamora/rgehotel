<?php /** @var array $settings */
$s = fn($k, $d = '') => e($settings[$k] ?? $d);
?>
<form method="post" action="<?= e(url('/admin/settings')) ?>">
  <?= csrf_field() ?>
  <div class="panel"><div class="panel__head"><h2>Homepage</h2></div><div class="panel__body"><div class="form-grid">
    <div class="field full"><label>Hero headline</label><input name="hero_headline" value="<?= $s('hero_headline') ?>"></div>
    <div class="field full"><label>Hero subheading</label><input name="hero_subhead" value="<?= $s('hero_subhead') ?>"></div>
    <div class="field"><label>Hero image (poster / fallback)</label><input name="hero_image" value="<?= $s('hero_image','general/hero-island') ?>" placeholder="general/hero-island"><small class="muted">Normalized image base, e.g. <code>general/hero-island</code>.</small></div>
    <div class="field"><label>Hero background video</label><input name="hero_video" value="<?= $s('hero_video') ?>" placeholder="video/hero.mp4"><small class="muted">Path under <code>assets/</code>, e.g. <code>video/hero.mp4</code>. Leave blank to use the image only.</small></div>
    <div class="field full"><label>Intro heading</label><input name="intro_heading" value="<?= $s('intro_heading') ?>"></div>
    <div class="field full"><label>Intro body</label><textarea name="intro_body" rows="3"><?= $s('intro_body') ?></textarea></div>
  </div></div></div>

  <div class="panel"><div class="panel__head"><h2>Contact &amp; Social</h2></div><div class="panel__body"><div class="form-grid">
    <div class="field"><label>Contact email</label><input name="contact_email" value="<?= $s('contact_email') ?>"></div>
    <div class="field"><label>Contact phone</label><input name="contact_phone" value="<?= $s('contact_phone') ?>"></div>
    <div class="field full"><label>Address</label><input name="contact_address" value="<?= $s('contact_address') ?>"></div>
    <div class="field"><label>Facebook URL</label><input name="facebook_url" value="<?= $s('facebook_url') ?>"></div>
    <div class="field"><label>Instagram URL</label><input name="instagram_url" value="<?= $s('instagram_url') ?>"></div>
  </div></div></div>

  <div class="panel"><div class="panel__head"><h2>Payments</h2></div><div class="panel__body"><div class="form-grid">
    <div class="field"><label>Online payment fee (%)</label>
      <input type="number" step="0.01" min="0" name="online_fee_percent" value="<?= $s('online_fee_percent','0') ?>">
      <small class="muted">Surcharge added on top of the total for online card/e-wallet payments (Xendit). Set <code>0</code> to disable. Cash / pay-at-hotel are never charged this fee.</small>
    </div>
  </div></div></div>

  <div class="panel"><div class="panel__head"><h2>Features</h2></div><div class="panel__body">
    <label class="checkbox"><input type="checkbox" name="restaurant_published" value="1" <?= ($settings['restaurant_published']??'0')==='1'?'checked':'' ?>> <span>Publish restaurant page on live site</span></label>
  </div></div>

  <div class="row-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save settings</button></div>
</form>
