<?php
/** @var string $pageTitle @var string|null $pageSub @var string|null $heroImage @var array|null $crumbs */
$heroImage = $heroImage ?? 'general/beach';
$GLOBALS['heroPreload'] = img_url($heroImage, 'full'); // primes LCP preload in <head>
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
