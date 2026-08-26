<?php
$spaces = [
    ['7', 'A warm welcome', 'Our exterior sets the tone: relaxed, bright and close to the island life.'],
    ['1', 'Arrive with ease', 'A welcoming reception and a team ready to help shape your Leyte stay.'],
    ['2', 'Settle into the lounge', 'Take a slow moment between island adventures in a comfortable shared space.'],
    ['3', 'Gather together', 'A practical shared kitchen gives longer stays and group trips room to breathe.'],
    ['6', 'Move through the day', 'Clean, considered details carry through the hotel from arrival to room.'],
    ['4', 'Freshen up', 'Bright bathrooms and hot showers make coming back from the sea feel easy.'],
    ['5', 'Small comforts', 'Thoughtful in-room touches are ready for the moments between excursions.'],
    ['8', 'Room to reset', 'Simple, useful amenities keep the focus on rest, connection and the coast.'],
    ['9', 'Ready for the morning', 'Guest essentials are arranged to make each new island day uncomplicated.'],
    ['10', 'A proper hot shower', 'Rinse off the salt, slow down and settle back into your evening.'],
    ['11', 'Personal service', 'From the front desk to the details, hospitality here is personal and warm.'],
    ['12', 'A place to return to', 'Come back from Kalanggaman to a calm, comfortable base in Palompon.'],
];
?>
<?= partial('partials.page-hero', [
    'pageTitle' => 'The Resort',
    'pageSub' => 'A closer look at the spaces, details and easy island rhythm of RGE Hotel.',
    'heroImage' => 'general/hero-island',
    'crumbs' => ['The Resort' => null],
]) ?>

<section class="section resort-intro">
  <div class="container resort-intro__grid">
    <div>
      <span class="eyebrow">Inside RGE Hotel</span>
      <h2>Stay close to what matters.</h2>
    </div>
    <p>From the first welcome to the last coffee before the boat leaves, RGE Hotel is designed as a comfortable base for slow mornings, full days and easy evenings by the coast.</p>
  </div>
</section>

<section class="section tone-sand">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">The spaces</span>
      <h2>Arrive. Gather. Unwind.</h2>
      <p>Explore the shared spaces and guest comforts that make the hotel feel like part of the trip, not just a place to sleep.</p>
    </div>
    <div class="resort-gallery">
      <?php foreach ($spaces as $index => [$image, $title, $description]): ?>
        <article class="resort-gallery__item <?= $index === 0 ? 'resort-gallery__item--feature' : '' ?> reveal">
          <a href="<?= e(img_url('amenities/' . $image, 'full')) ?>" data-lightbox>
            <?= img_tag('amenities/' . $image, $title, '', $index === 0 ? '(max-width:700px) 100vw, 66vw' : '(max-width:700px) 100vw, 33vw') ?>
          </a>
          <div class="resort-gallery__copy">
            <span class="resort-gallery__number">0<?= $index + 1 ?></span>
            <h3><?= e($title) ?></h3>
            <p><?= e($description) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section tone-ink">
  <div class="container resort-close">
    <div>
      <span class="eyebrow">Your island base</span>
      <h2>Make your stay the starting point.</h2>
    </div>
    <div>
      <p>Sleep well, step outside and let our team arrange the rest. Kalanggaman, Leyte and the water are all within reach.</p>
      <a class="btn btn-primary mt-3" href="<?= e(url('/accommodations')) ?>"><?= icon('calendar') ?> Find your room</a>
    </div>
  </div>
</section>
