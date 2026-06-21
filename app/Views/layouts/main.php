<?php
use App\Models\Setting;
$title = $title ?? 'RGE Hotel — Beachfront Escape near Kalanggaman Island, Leyte';
$metaDescription = $metaDescription ?? 'RGE Hotel — a chic, modern beachfront hotel in Palompon, Leyte, the gateway to Kalanggaman Island. Rooms, island-hopping tours, water sports and more.';
$active = $active ?? '';
$ogImage = isset($ogImage) ? $ogImage : url('assets/img/general/hero-island-full.webp');
$nav = [
    'accommodations' => ['Accommodations', url('/accommodations')],
    'packages'       => ['Packages', url('/packages')],
    'services'       => ['Tours & Services', url('/services')],
    'offers'         => ['Offers', url('/offers')],
    'reviews'        => ['Reviews', url('/reviews')],
    'about'          => ['About', url('/about')],
    'contact'        => ['Contact', url('/contact')],
];
$fb = Setting::get('facebook_url'); $ig = Setting::get('instagram_url');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($metaDescription) ?>">
<link rel="canonical" href="<?= e(url($_SERVER['REQUEST_URI'] ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '/')) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($metaDescription) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta property="og:site_name" content="RGE Hotel">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset('img/favicon-32.png')) ?>">
<link rel="apple-touch-icon" href="<?= e(asset('img/apple-touch-icon.png')) ?>">
<meta name="theme-color" content="#0e7c86">
<link rel="preload" as="font" type="font/woff2" href="<?= e(asset('fonts/inter-latin.woff2')) ?>" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<?= $jsonld ?? '' ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<header class="site-header">
  <div class="container nav">
    <a class="brand" href="<?= e(url('/')) ?>" aria-label="RGE Hotel home">
      <img src="<?= e(img_url('general/logo','full')) ?>" alt="RGE Hotel">
    </a>
    <nav class="nav-links" id="navLinks">
      <?php foreach ($nav as $key => [$label, $href]): ?>
        <a href="<?= e($href) ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="nav-cta">
      <a class="btn btn-primary btn-sm" href="<?= e(url('/accommodations')) ?>"><?= icon('calendar') ?><span class="btn-text">Book Now</span></a>
      <button class="nav-toggle" id="navToggle" aria-label="Menu"><?= icon('menu') ?></button>
    </div>
  </div>
</header>

<main>
<?= $content ?>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <a class="brand" href="<?= e(url('/')) ?>"><img src="<?= e(img_url('general/logo','full')) ?>" alt="RGE Hotel"></a>
        <p class="mt-2" style="max-width:34ch"><?= e(Setting::get('intro_body', 'A modern beachfront escape at the gateway to Kalanggaman Island, Leyte.')) ?></p>
        <div class="socials-row">
          <?php if ($fb): ?><a href="<?= e($fb) ?>" target="_blank" rel="noopener" aria-label="Facebook"><?= icon('facebook','',18) ?></a><?php endif; ?>
          <?php if ($ig): ?><a href="<?= e($ig) ?>" target="_blank" rel="noopener" aria-label="Instagram"><?= icon('instagram','',18) ?></a><?php endif; ?>
          <a href="mailto:<?= e(Setting::get('contact_email','info@rgehotel.com')) ?>" aria-label="Email"><?= icon('mail','',18) ?></a>
        </div>
      </div>
      <div>
        <h4>Explore</h4>
        <ul class="footer-links">
          <li><a href="<?= e(url('/accommodations')) ?>">Accommodations</a></li>
          <li><a href="<?= e(url('/packages')) ?>">Packages</a></li>
          <li><a href="<?= e(url('/services')) ?>">Tours &amp; Services</a></li>
          <li><a href="<?= e(url('/offers')) ?>">Offers</a></li>
        </ul>
      </div>
      <div>
        <h4>Hotel</h4>
        <ul class="footer-links">
          <li><a href="<?= e(url('/about')) ?>">About</a></li>
          <li><a href="<?= e(url('/reviews')) ?>">Reviews</a></li>
          <li><a href="<?= e(url('/contact')) ?>">Contact</a></li>
        </ul>
      </div>
      <div>
        <h4>Visit Us</h4>
        <ul class="footer-links">
          <li><?= icon('map-pin','',16) ?> <?= e(Setting::get('contact_address','Palompon, Leyte, Philippines')) ?></li>
          <li><a href="mailto:<?= e(Setting::get('contact_email','info@rgehotel.com')) ?>"><?= icon('mail','',16) ?> <?= e(Setting::get('contact_email','info@rgehotel.com')) ?></a></li>
          <?php if (Setting::get('contact_phone')): ?><li><?= icon('phone','',16) ?> <?= e(Setting::get('contact_phone')) ?></li><?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> RGE Hotel. All rights reserved.</span>
      <span>Kalanggaman Island · Leyte · Philippines</span>
    </div>
  </div>
</footer>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
