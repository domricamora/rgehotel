<div class="login-wrap">
  <div class="login-card">
    <div class="logo"><img src="<?= e(img_url('general/logo','full')) ?>" alt="RGE Hotel"></div>
    <h1 style="text-align:center;font-size:1.3rem;margin-bottom:4px">Welcome back</h1>
    <p class="muted" style="text-align:center;font-size:.9rem;margin-bottom:20px">Sign in to the management console</p>
    <?= partial('partials.flash') ?>
    <form method="post" action="<?= e(url('/admin/login')) ?>">
      <?= csrf_field() ?>
      <div class="field"><label>Email</label><input type="email" name="email" value="<?= e(old('email')) ?>" required autofocus></div>
      <div class="field mt-2"><label>Password</label><input type="password" name="password" required></div>
      <button class="btn btn-primary" style="width:100%;justify-content:center" type="submit">Sign In</button>
    </form>
    <p class="muted" style="text-align:center;font-size:.8rem;margin-top:18px">Demo: admin@rgehotel.com / password</p>
  </div>
</div>
