<?php
/** @var string $pageTitle @var string|null $pageSub @var string|null $heroImage @var array|null $crumbs */
$heroImage = $heroImage ?? 'general/beach';
$GLOBALS['heroPreload'] = img_url($heroImage, 'full'); // primes LCP preload in <head>

// BreadcrumbList structured data from the crumb trail
if (!empty($crumbs)) {
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $ld = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => []];
    $ld['itemListElement'][] = ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => site_url('/')];
    $pos = 2;
    foreach ($crumbs as $label => $href) {
        $ld['itemListElement'][] = [
            '@type' => 'ListItem', 'position' => $pos++, 'name' => $label,
            'item' => $href ? site_url($href) : site_url($currentPath),
        ];
    }
    echo '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}
?>
<section class="page-hero">
  <div class="page-hero__bg" style="background-image:url('<?= e(img_url($heroImage, 'full')) ?>')"></div>
  <div class="container">
    <?php if (!empty($crumbs)): ?>
      <nav class="breadcrumb">
        <a href="<?= e(url('/')) ?>">Home</a>
        <?php foreach ($crumbs as $label => $href): ?>
          &nbsp;/&nbsp;<?php if ($href): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><?php else: ?><?= e($label) ?><?php endif; ?>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>
    <h1><?= e($pageTitle) ?></h1>
    <?php if (!empty($pageSub)): ?><p><?= e($pageSub) ?></p><?php endif; ?>
  </div>
</section>
