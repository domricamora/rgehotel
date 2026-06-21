<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title ?? 'Invoice') ?> — RGE Hotel</title>
<style>
  @font-face{font-family:'Inter';font-weight:100 900;font-display:swap;src:url('<?= e(asset('fonts/inter-latin.woff2')) ?>') format('woff2');}
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:'Inter',sans-serif;color:#0b2229;background:#f5f7f7;padding:30px;line-height:1.5}
  .sheet{max-width:760px;margin:0 auto;background:#fff;border:1px solid #e7ebec;border-radius:14px;padding:44px}
  .toolbar{max-width:760px;margin:0 auto 16px;display:flex;justify-content:flex-end;gap:8px}
  .btn{display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:10px;font-weight:600;font-size:.88rem;border:1px solid #e7ebec;background:#fff;cursor:pointer;text-decoration:none;color:#0b2229}
  .btn-primary{background:#0e7c86;color:#fff;border-color:#0e7c86}
  @media print{.toolbar{display:none}body{background:#fff;padding:0}.sheet{border:0;border-radius:0}}
</style>
</head>
<body>
  <div class="toolbar">
    <a class="btn" href="<?= e(url('/admin/bookings')) ?>">← Back</a>
    <button class="btn btn-primary" onclick="window.print()">Print / Save PDF</button>
  </div>
  <div class="sheet"><?= $content ?></div>
</body>
</html>
