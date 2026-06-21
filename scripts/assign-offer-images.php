<?php
/**
 * Assign per-offer photos to existing offers from the media manifest — without
 * reseeding (seed.php truncates bookings/payments and must not be run on live data).
 *
 * Run after dropping offers/<slug>.jpg files and running image-normalize.php:
 *   php scripts/assign-offer-images.php
 */
require __DIR__ . '/../app/Core/helpers.php';
spl_autoload_register(function ($class) {
    if (!str_starts_with($class, 'App\\')) return;
    $f = __DIR__ . '/../app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($f)) require $f;
});

use App\Core\Database;

$manifestPath = __DIR__ . '/../storage/media-manifest.json';
if (!is_file($manifestPath)) {
    fwrite(STDERR, "media-manifest.json not found — run image-normalize.php first.\n");
    exit(1);
}
$manifest = json_decode(file_get_contents($manifestPath), true) ?: [];
$offers = $manifest['offers'] ?? [];
if (!$offers) {
    echo "No per-offer images in the manifest yet. Drop offers/<slug>.jpg files and run image-normalize.php.\n";
    exit(0);
}

$db = Database::instance();
$updated = 0;
foreach ($offers as $slug => $base) {
    $n = $db->update('offers', ['image' => $base], ['slug' => $slug]);
    if ($n > 0) { echo "  $slug -> $base\n"; $updated += $n; }
    else echo "  (no offer with slug '$slug')\n";
}
echo "\nDone. Updated $updated offer image(s).\n";
