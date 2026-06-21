<?php /** @var array $menu */ ?>
<?= partial('partials.page-hero', [
    'pageTitle' => 'Restaurant',
    'pageSub'   => 'Fresh seafood and Filipino favourites, served by the beach.',
    'heroImage' => 'general/restaurant',
    'crumbs'    => ['Restaurant' => null],
]) ?>
<section class="section">
  <div class="container" style="max-width:860px">
    <?php foreach ($menu as $cat): ?>
      <div class="section-head" style="margin-bottom:18px"><span class="eyebrow"><?= e($cat['name']) ?></span><?php if($cat['description']):?><p style="margin-top:6px"><?= e($cat['description']) ?></p><?php endif;?></div>
      <div class="mb-3">
        <?php foreach ($cat['items'] as $item): ?>
        <div style="display:flex;justify-content:space-between;gap:16px;padding:14px 0;border-bottom:1px solid var(--line)">
          <div>
            <strong><?= e($item['name']) ?></strong>
            <?php if($item['description']):?><div class="muted" style="font-size:.9rem"><?= e($item['description']) ?></div><?php endif;?>
          </div>
          <div class="price" style="font-size:1.05rem;white-space:nowrap"><?= money($item['price']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>
