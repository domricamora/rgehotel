<?php
use App\Models\Setting;
/** @var array $rooms @var array $services @var array $packages @var array $offers @var array $reviews @var array $rating */
$heroImg = Setting::get('hero_image', 'general/hero-island');
$heroVideo = Setting::get('hero_video', '');   // e.g. 'video/hero.mp4' under assets/ — empty = photo only
?>
<!-- HERO -->
<section class="hero">
  <div class="hero__bg" style="background-image:url('<?= e(img_url($heroImg, 'full')) ?>')"></div>
  <?php if ($heroVideo): ?>
  <video class="hero__video" autoplay muted loop playsinline preload="metadata" poster="<?= e(img_url($heroImg, 'full')) ?>" aria-hidden="true">
    <source src="<?= e(asset($heroVideo)) ?>" type="video/mp4">
  </video>
  <?php endif; ?>
  <div class="container hero__inner">
    <span class="eyebrow">Kalanggaman Island · Leyte, Philippines</span>
    <h1><?= e(Setting::get('hero_headline', 'Where the island begins')) ?></h1>
    <p><?= e(Setting::get('hero_subhead', 'A modern beachfront escape at the gateway to Kalanggaman Island.')) ?></p>
    <div class="hero__actions">
      <a class="btn btn-primary" href="<?= e(url('/accommodations')) ?>"><?= icon('calendar') ?> Book Your Stay</a>
      <a class="btn btn-ghost" href="<?= e(url('/services')) ?>"><?= icon('ship') ?> Explore Tours</a>
    </div>
  </div>
</section>

<div class="container">
  <?= partial('partials.book-bar', ['check_in' => '', 'check_out' => '']) ?>
</div>

<!-- INTRO / FEATURES -->
<section class="section">
  <div class="container">
    <div class="section-head center">
      <span class="eyebrow">Welcome to RGE Hotel</span>
      <h2><?= e(Setting::get('intro_heading', 'Your island, beautifully simple')) ?></h2>
      <p><?= e(Setting::get('intro_body')) ?></p>
    </div>
    <div class="grid grid-4">
      <?php foreach ([
        ['waves','Steps from the sea','White-sand shoreline and the clearest water in the Visayas, right at your doorstep.'],
        ['ship','Kalanggaman trips','Daily island-hopping to the famous Kalanggaman sandbar and beyond.'],
        ['sparkles','Clean & modern','Bright, air-conditioned rooms with thoughtful, contemporary comfort.'],
        ['concierge-bell','Warm hospitality','Genuine Filipino service from a team that treats you like family.'],
      ] as [$ic, $t, $d]): ?>
        <div class="feature reveal">
          <div class="ico"><?= icon($ic) ?></div>
          <h3 style="font-size:1.15rem"><?= e($t) ?></h3>
          <p style="font-size:.94rem;margin-top:6px"><?= e($d) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- FEATURED ROOMS -->
<section class="section tone-sand">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Accommodations</span>
      <h2>Rooms with room to breathe</h2>
      <p>From cosy doubles to spacious family and group rooms — find your perfect island base.</p>
    </div>
    <div class="grid grid-3">
      <?php foreach ($rooms as $room) echo partial('partials.room-card', ['room' => $room]); ?>
    </div>
    <div class="center mt-4"><a class="btn btn-teal" href="<?= e(url('/accommodations')) ?>">View all accommodations <?= icon('arrow-right') ?></a></div>
  </div>
</section>

<!-- PACKAGES -->
<?php if ($packages): ?>
<section class="section">
  <div class="container">
    <div class="section-head center">
      <span class="eyebrow">Curated Stays</span>
      <h2>Packages &amp; deals</h2>
      <p>Bundle your room with breakfast and Leyte's best experiences.</p>
    </div>
    <div class="grid grid-3">
      <?php foreach ($packages as $p): ?>
      <article class="card reveal">
        <a class="card__media" href="<?= e(url('/packages/' . $p['slug'])) ?>"><?= img_tag($p['image'] ?? null, $p['name']) ?>
          <?php if ($p['original_price'] && $p['original_price'] > $p['price']): ?><span class="card__badge">Save <?= money($p['original_price'] - $p['price']) ?></span><?php endif; ?>
        </a>
        <div class="card__body">
          <h3><a href="<?= e(url('/packages/' . $p['slug'])) ?>"><?= e($p['name']) ?></a></h3>
          <p style="font-size:.95rem"><?= e($p['summary']) ?></p>
          <div class="card__foot">
            <div class="price">
              <?php if ($p['original_price'] && $p['original_price'] > $p['price']): ?><del><?= money($p['original_price']) ?></del><?php endif; ?>
              <?= money($p['price']) ?>
            </div>
            <a class="btn btn-outline btn-sm" href="<?= e(url('/packages/' . $p['slug'])) ?>">Details <?= icon('chevron-right','',16) ?></a>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- EXPERIENCES / SERVICES -->
<?php if ($services): ?>
<section class="section tone-teal">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Tours &amp; Experiences</span>
      <h2>Adventures beyond the shore</h2>
      <p>Island hopping, diving, water sports and Leyte tours — all arranged for you.</p>
    </div>
    <div class="grid grid-4">
      <?php foreach ($services as $s): ?>
      <article class="card reveal">
        <a class="card__media" href="<?= e(url('/services/' . $s['slug'])) ?>"><?= img_tag($s['image'] ?? null, $s['name'], '', '(max-width:640px) 100vw, 25vw') ?></a>
        <div class="card__body">
          <span class="chip"><?= e(ucwords(str_replace('_',' ',$s['category']))) ?></span>
          <h3 style="font-size:1.1rem"><a href="<?= e(url('/services/' . $s['slug'])) ?>"><?= e($s['name']) ?></a></h3>
          <div class="card__foot">
            <?php if ($s['price']): ?><div class="price" style="font-size:1.05rem"><?= money($s['price']) ?> <small><?= e($s['price_unit']) ?></small></div><?php endif; ?>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <div class="center mt-4"><a class="btn btn-teal" href="<?= e(url('/services')) ?>">All tours &amp; services <?= icon('arrow-right') ?></a></div>
  </div>
</section>
<?php endif; ?>

<!-- OFFERS -->
<?php if ($offers): ?>
<section class="section">
  <div class="container">
    <div class="section-head center"><span class="eyebrow">Limited Time</span><h2>Ongoing offers</h2></div>
    <div class="grid grid-3">
      <?php foreach ($offers as $o): ?>
      <article class="card reveal" style="background:linear-gradient(135deg,#fff,#fff);border-color:#e9d8c5">
        <div class="card__body">
          <span class="chip" style="background:#f6ece2;color:var(--coral-deep)"><?= icon('percent','',15) ?> <?= $o['discount_type']==='percent' ? (int)$o['discount_value'].'% OFF' : money($o['discount_value']).' OFF' ?></span>
          <h3><?= e($o['title']) ?></h3>
          <p style="font-size:.95rem"><?= e($o['subtitle']) ?></p>
          <?php if ($o['code']): ?><p class="mt-2"><strong>Code:</strong> <code style="background:var(--sand);padding:3px 8px;border-radius:0"><?= e($o['code']) ?></code></p><?php endif; ?>
          <div class="card__foot"><a class="btn btn-primary btn-sm" href="<?= e(url('/accommodations')) ?>">Book &amp; save</a></div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- REVIEWS -->
<?php if ($reviews): ?>
<section class="section tone-sand">
  <div class="container">
    <div class="section-head center">
      <span class="eyebrow">Guest Love</span>
      <h2>Rated <?= e($rating['avg'] ?? '5.0') ?> by our guests</h2>
      <p><?= stars($rating['avg'] ?? 5) ?> &nbsp;from <?= (int)$rating['count'] ?> verified reviews</p>
    </div>
    <div class="grid grid-3">
      <?php foreach ($reviews as $rv): ?>
      <div class="review reveal">
        <div class="review__head"><?= stars($rv['rating']) ?></div>
        <h3 style="font-size:1.05rem"><?= e($rv['title']) ?></h3>
        <p style="font-size:.95rem;margin-top:6px">“<?= e($rv['body']) ?>”</p>
        <p class="mt-2"><span class="review__name"><?= e($rv['author_name']) ?></span> <span class="review__country">· <?= e($rv['author_country']) ?></span></p>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="center mt-4"><a class="btn btn-outline" href="<?= e(url('/reviews')) ?>">Read all reviews <?= icon('arrow-right') ?></a></div>
  </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section class="section tone-ink" id="newsletter">
  <div class="container center">
    <span class="eyebrow" style="color:#cbb88c">Your island escape awaits</span>
    <h2>Ready to wake up by the sea?</h2>
    <p style="max-width:46ch;margin:12px auto 0">Reserve your room at RGE Hotel and start planning your Kalanggaman adventure today.</p>
    <div class="mt-4 flex items-center gap-2 wrap" style="justify-content:center">
      <a class="btn btn-primary" href="<?= e(url('/accommodations')) ?>"><?= icon('calendar') ?> Book Now</a>
      <a class="btn btn-ghost" href="<?= e(url('/contact')) ?>"><?= icon('mail') ?> Talk to Us</a>
    </div>
  </div>
</section>
