<?php /** @var array $reviews @var array $rating */ ?>
<?= partial('partials.page-hero', [
    'pageTitle' => 'Guest Reviews',
    'pageSub'   => 'Real stories from guests who made RGE Hotel their island home.',
    'heroImage' => 'general/sunset',
    'crumbs'    => ['Reviews' => null],
]) ?>
<section class="section">
  <div class="container">
    <?= partial('partials.flash') ?>
    <div class="section-head center">
      <h2>Rated <?= e($rating['avg'] ?? '5.0') ?> / 5</h2>
      <p><?= stars($rating['avg'] ?? 5, 20) ?> &nbsp;from <?= (int)$rating['count'] ?> verified guest reviews</p>
    </div>
    <div class="grid grid-3">
      <?php foreach ($reviews as $rv): ?>
      <div class="review reveal">
        <div class="review__head"><?= stars($rv['rating']) ?><?php if($rv['stay_date']):?><span class="review__country"><?= e(date('M Y', strtotime($rv['stay_date']))) ?></span><?php endif;?></div>
        <h3 style="font-size:1.05rem"><?= e($rv['title']) ?></h3>
        <p style="font-size:.94rem;margin-top:6px">“<?= e($rv['body']) ?>”</p>
        <p class="mt-2"><span class="review__name"><?= e($rv['author_name']) ?></span> <span class="review__country">· <?= e($rv['author_country']) ?></span></p>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Leave a review -->
    <div class="section-head center mt-4" style="margin-top:64px"><span class="eyebrow">Stayed with us?</span><h2>Share your experience</h2></div>
    <form method="post" action="<?= e(url('/reviews')) ?>" style="max-width:640px;margin:0 auto">
      <?= csrf_field() ?>
      <div class="field-row">
        <div class="field"><label>Your name *</label><input name="author_name" required></div>
        <div class="field"><label>Country</label><input name="author_country" placeholder="e.g. Philippines"></div>
      </div>
      <div class="field-row">
        <div class="field"><label>Rating *</label><select name="rating" required><?php for($i=5;$i>=1;$i--) echo "<option value=\"$i\">$i star".($i>1?'s':'')."</option>"; ?></select></div>
        <div class="field"><label>Title</label><input name="title" placeholder="Summarise your stay"></div>
      </div>
      <div class="field"><label>Your review *</label><textarea name="body" rows="4" required placeholder="Tell us about your stay..."></textarea></div>
      <button class="btn btn-primary" type="submit"><?= icon('check') ?> Submit Review</button>
      <p class="muted" style="font-size:.82rem;margin-top:10px">Reviews appear after approval by our team.</p>
    </form>
  </div>
</section>
