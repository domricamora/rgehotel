<?php
use App\Core\Auth;
$user = Auth::user();
$active = $active ?? '';
$pageTitle = $pageTitle ?? ($title ?? 'Dashboard');

// Nav items: [key, label, href, icon, permission]
$nav = [
    ['dashboard', 'Dashboard', url('/admin'), 'layout-dashboard', null],
    ['bookings', 'Bookings', url('/admin/bookings'), 'calendar', 'bookings.view'],
    ['rooms', 'Rooms', url('/admin/rooms'), 'bed', 'rooms.view'],
    ['housekeeping', 'Housekeeping', url('/admin/housekeeping'), 'sparkles', 'housekeeping.view'],
];
$content_nav = [
    ['services', 'Services & Tours', url('/admin/services'), 'ship', 'services.manage'],
    ['packages', 'Packages', url('/admin/packages'), 'tag', 'packages.manage'],
    ['offers', 'Offers', url('/admin/offers'), 'percent', 'offers.manage'],
    ['reviews', 'Reviews', url('/admin/reviews'), 'star', 'reviews.moderate'],
    ['restaurant', 'Restaurant', url('/admin/restaurant'), 'utensils', 'restaurant.manage'],
];
$admin_nav = [
    ['payments', 'Payments', url('/admin/payments'), 'percent', 'payments.view'],
    ['users', 'Users', url('/admin/users'), 'users', 'users.manage'],
    ['settings', 'Settings', url('/admin/settings'), 'sparkles', 'settings.manage'],
];
$renderNav = function(array $items) use ($active) {
    foreach ($items as [$key, $label, $href, $ic, $perm]) {
        if ($perm && !Auth::can($perm)) continue;
        $cls = $active === $key ? ' class="active"' : '';
        echo "<a href=\"" . e($href) . "\"$cls>" . icon($ic) . "<span>" . e($label) . "</span></a>";
    }
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> — RGE Hotel Admin</title>
<link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset('img/favicon-32.png')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body>
<div class="admin">
  <aside class="sidebar">
    <a class="logo" href="<?= e(url('/admin')) ?>"><img src="<?= e(img_url('general/logo','full')) ?>" alt="RGE Hotel"></a>
    <nav>
      <?php $renderNav($nav); ?>
      <?php if (Auth::can('services.manage')||Auth::can('packages.manage')||Auth::can('offers.manage')||Auth::can('reviews.moderate')||Auth::can('restaurant.manage')): ?>
        <div class="group-label">Content</div>
        <?php $renderNav($content_nav); ?>
      <?php endif; ?>
      <?php if (Auth::can('payments.view')||Auth::can('users.manage')||Auth::can('settings.manage')): ?>
        <div class="group-label">Administration</div>
        <?php $renderNav($admin_nav); ?>
      <?php endif; ?>
      <div class="group-label">&nbsp;</div>
      <a href="<?= e(url('/')) ?>" target="_blank"><?= icon('arrow-right') ?><span>View website</span></a>
    </nav>
  </aside>
  <div class="main">
    <header class="topbar">
      <h1><?= e($pageTitle) ?></h1>
      <div class="user">
        <div style="text-align:right">
          <div style="font-weight:600"><?= e($user['name'] ?? '') ?></div>
          <div class="muted" style="font-size:.78rem"><?= e($user['role_name'] ?? '') ?></div>
        </div>
        <div class="avatar"><?= e(strtoupper(substr($user['name'] ?? 'U',0,1))) ?></div>
        <form method="post" action="<?= e(url('/admin/logout')) ?>"><?= csrf_field() ?><button class="btn btn-outline btn-sm" title="Sign out"><?= icon('log-out') ?></button></form>
      </div>
    </header>
    <div class="content">
      <?= partial('partials.flash') ?>
      <?= $content ?>
    </div>
  </div>
</div>
</body>
</html>
