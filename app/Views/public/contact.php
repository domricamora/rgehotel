<?php
use App\Models\Setting;
/** @var array|null $page */
?>
<?= partial('partials.page-hero', [
    'pageTitle' => 'Contact Us',
    'pageSub'   => 'We\'d love to help plan your island escape.',
    'heroImage' => 'general/kalanggaman',
    'crumbs'    => ['Contact' => null],
]) ?>
<section class="section">
  <div class="container">
    <?= partial('partials.flash') ?>
    <div class="detail-layout">
      <div>
        <h2 style="font-size:1.5rem">Send us a message</h2>
        <p class="mt-2"><?= e($page['body'] ?? 'Reach us by email or send a message below and our team will get back to you promptly.') ?></p>
        <form method="post" action="<?= e(url('/contact')) ?>" class="mt-3">
          <?= csrf_field() ?>
          <div class="field-row">
            <div class="field"><label>Name *</label><input name="name" required></div>
            <div class="field"><label>Email *</label><input type="email" name="email" required></div>
          </div>
          <div class="field-row">
            <div class="field"><label>Phone</label><input name="phone"></div>
            <div class="field"><label>Subject</label><input name="subject"></div>
          </div>
          <div class="field"><label>Message *</label><textarea name="message" rows="5" required></textarea></div>
          <button class="btn btn-primary" type="submit"><?= icon('mail') ?> Send Message</button>
        </form>
      </div>
      <aside>
        <div class="booking-card">
          <h3 style="font-size:1.15rem">Get in touch</h3>
          <ul class="footer-links mt-3" style="color:var(--slate)">
            <li style="margin-bottom:14px"><?= icon('map-pin','',18) ?> <?= e(Setting::get('contact_address','Palompon, Leyte, Philippines')) ?></li>
            <li style="margin-bottom:14px"><a href="mailto:<?= e(Setting::get('contact_email','info@rgehotel.com')) ?>"><?= icon('mail','',18) ?> <?= e(Setting::get('contact_email','info@rgehotel.com')) ?></a></li>
            <?php if (Setting::get('contact_phone')): ?><li><?= icon('phone','',18) ?> <?= e(Setting::get('contact_phone')) ?></li><?php endif; ?>
          </ul>
          <hr style="border:0;border-top:1px solid var(--line);margin:16px 0">
          <p style="font-size:.92rem">Planning a Kalanggaman trip or a group stay? Tell us your dates and we'll craft the perfect package.</p>
        </div>
      </aside>
    </div>
  </div>
</section>
