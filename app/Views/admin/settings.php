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
      <small class="muted">Surcharge added on top of the total for online card/e-wallet payments (PayMongo). Set <code>0</code> to disable. Cash / pay-at-hotel are never charged this fee.</small>
    </div>
  </div></div></div>

  <?php if (!empty($credentialStatus)): ?>
  <div class="panel"><div class="panel__head"><h2>Gateway API credentials</h2></div><div class="panel__body">
    <p class="muted" style="max-width:70ch">Only the super admin can access this section. Existing secrets are never shown. Leave a secret field blank to keep its current value.</p>
    <div class="form-grid mt-3">
      <div class="field"><label>PayMongo enabled</label><label class="checkbox"><input type="checkbox" name="paymongo_enabled" value="1" <?= ($settings['paymongo_enabled'] ?? '1') === '1' ? 'checked' : '' ?>> <span>Enable PayMongo</span></label></div>
      <div class="field"><label>PayMongo mode</label><select name="paymongo_mode"><option value="test" <?= ($settings['paymongo_mode'] ?? 'test') === 'test' ? 'selected' : '' ?>>Test</option><option value="live" <?= ($settings['paymongo_mode'] ?? '') === 'live' ? 'selected' : '' ?>>Live</option></select></div>
      <div class="field full"><label>PayMongo secret key</label><input type="password" name="paymongo_secret_key" value="" autocomplete="new-password" placeholder="<?= !empty($credentialStatus['paymongo']) ? 'Configured - enter a replacement to change' : 'sk_test_... or sk_live_...' ?>"><small class="muted">Status: <?= !empty($credentialStatus['paymongo']) ? 'configured' : 'not configured' ?>.</small></div>
      <div class="field full"><label>PayMongo webhook secret</label><input type="password" name="paymongo_webhook_secret" value="" autocomplete="new-password" placeholder="<?= !empty($credentialStatus['paymongo_webhook']) ? 'Configured - enter a replacement to change' : 'whsk_...' ?>"><small class="muted">Status: <?= !empty($credentialStatus['paymongo_webhook']) ? 'configured' : 'not configured' ?>.</small></div>
      <div class="field"><label>PayPal enabled</label><label class="checkbox"><input type="checkbox" name="paypal_enabled" value="1" <?= ($settings['paypal_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> <span>Enable PayPal</span></label></div>
      <div class="field"><label>PayPal mode</label><select name="paypal_mode"><option value="sandbox" <?= ($settings['paypal_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>Sandbox</option><option value="live" <?= ($settings['paypal_mode'] ?? '') === 'live' ? 'selected' : '' ?>>Live</option></select></div>
      <div class="field full"><label>PayPal client ID</label><input type="password" name="paypal_client_id" value="" autocomplete="new-password" placeholder="<?= !empty($credentialStatus['paypal']) ? 'Configured - enter a replacement to change' : 'Client ID' ?>"><small class="muted">Status: <?= !empty($credentialStatus['paypal']) ? 'configured' : 'not configured' ?>.</small></div>
      <div class="field full"><label>PayPal client secret</label><input type="password" name="paypal_client_secret" value="" autocomplete="new-password" placeholder="Client secret"></div>
      <div class="field full"><label>PayPal webhook ID</label><input type="password" name="paypal_webhook_id" value="" autocomplete="new-password" placeholder="<?= !empty($credentialStatus['paypal_webhook']) ? 'Configured - enter a replacement to change' : 'Webhook ID' ?>"><small class="muted">Status: <?= !empty($credentialStatus['paypal_webhook']) ? 'configured' : 'not configured' ?>.</small></div>
    </div>
  </div></div>
  <?php endif; ?>

  <div class="panel"><div class="panel__head"><h2>Features</h2></div><div class="panel__body">
    <label class="checkbox"><input type="checkbox" name="restaurant_published" value="1" <?= ($settings['restaurant_published']??'0')==='1'?'checked':'' ?>> <span>Publish restaurant page on live site</span></label>
  </div></div>

  <div class="row-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save settings</button></div>
</form>
