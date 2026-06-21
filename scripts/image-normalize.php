<?php
/**
 * Normalize the RGE media library into web-optimized WebP.
 * Produces two sizes per image: -thumb (600w) and -full (1400w).
 * Emits storage/media-manifest.json consumed by seed.php.
 *
 * Run: php scripts/image-normalize.php
 */
ini_set('memory_limit', '1024M');

$SRC = 'C:/Users/Nick/Documents/rge';
$OUT = __DIR__ . '/../assets/img';
$WIDTHS = ['thumb' => 600, 'full' => 1400];
// Hero imagery is shown full-bleed on large screens — render it sharper.
$HERO_WIDTHS = ['thumb' => 900, 'full' => 2000];

function loadImage(string $path) {
    $info = @getimagesize($path);
    if (!$info) return null;
    switch ($info[2]) {
        case IMAGETYPE_JPEG: return @imagecreatefromjpeg($path);
        case IMAGETYPE_PNG:  return @imagecreatefrompng($path);
        case IMAGETYPE_WEBP: return @imagecreatefromwebp($path);
        default: return null;
    }
}

function emit(string $srcPath, string $outBase, array $widths): bool {
    $im = loadImage($srcPath);
    if (!$im) { fwrite(STDERR, "  skip (unreadable): $srcPath\n"); return false; }
    $w = imagesx($im); $h = imagesy($im);
    @mkdir(dirname($outBase), 0777, true);
    foreach ($widths as $suffix => $maxW) {
        $tw = min($maxW, $w);
        $th = (int) round($h * ($tw / $w));
        $dst = imagecreatetruecolor($tw, $th);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $t = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $tw, $th, $t);
        imagecopyresampled($dst, $im, 0, 0, 0, 0, $tw, $th, $w, $h);
        imagewebp($dst, "$outBase-$suffix.webp", $suffix === 'thumb' ? 80 : 82);
        imagedestroy($dst);
    }
    imagedestroy($im);
    return true;
}

/** Process a folder's images (sorted), cap count, into <relBase>/N (relative to $OUT). */
function processFolder(string $dir, string $out, string $relBase, int $cap, array $widths): array {
    $files = [];
    foreach (['*.jpg','*.jpeg','*.png','*.JPG','*.PNG'] as $g) {
        foreach (glob("$dir/$g") as $f) $files[] = $f;
    }
    sort($files);
    $files = array_slice($files, 0, $cap);
    $bases = [];
    $i = 1;
    foreach ($files as $f) {
        $rel = "$relBase/$i";
        if (emit($f, "$out/$rel", $widths)) { $bases[] = $rel; $i++; }
    }
    return $bases;
}

$manifest = ['rooms' => [], 'amenities' => [], 'services' => [], 'general' => [], 'logo' => 'general/logo'];

echo "== Rooms ==\n";
// slug => source folder under booking/
$roomFolders = [
    'twin-room'          => 'booking/twin room',
    'double-room'        => 'booking/double room',
    'double-or-twin'     => 'booking/double or twin room',
    'triple-room'        => 'booking/triple room',
    'family-room'        => 'booking/family room',
    'suite'              => 'booking/suite',
    'barkada-room-a'     => 'booking/barkada room a',
    'barkada-room-b'     => 'booking/barkada room b',
];
foreach ($roomFolders as $slug => $folder) {
    $dir = "$SRC/$folder";
    if (!is_dir($dir)) { echo "  (missing) $folder\n"; continue; }
    $bases = processFolder($dir, $OUT, "rooms/$slug", 6, $WIDTHS);
    $manifest['rooms'][$slug] = $bases;
    echo "  $slug: " . count($bases) . " photos\n";
}

echo "== Amenities ==\n";
$amenityFiles = array_merge(
    glob("$SRC/amenities/slide-*.jpg"),
    glob("$SRC/amenities/view-*.jpg")
);
sort($amenityFiles);
$i = 1;
foreach (array_slice($amenityFiles, 0, 12) as $f) {
    if (emit($f, "$OUT/amenities/$i", $WIDTHS)) { $manifest['amenities'][] = "amenities/$i"; $i++; }
}
echo "  " . count($manifest['amenities']) . " amenity images\n";

echo "== General / hero ==\n";
// Hero stills render at $HERO_WIDTHS; drop a higher-resolution Kalanggaman file at
// hero-kalanggaman.jpg (preferred) and it becomes the hero poster/fallback.
$heroKeys = ['hero-island', 'kalanggaman'];
$general = [
    'hero-island'   => is_file("$SRC/hero-kalanggaman.jpg") ? "$SRC/hero-kalanggaman.jpg" : "$SRC/13-kalanggaman-island-secondary-banner.jpg",
    'beach'         => "$SRC/beach.jpg",
    'kalanggaman'   => is_file("$SRC/hero-kalanggaman.jpg") ? "$SRC/hero-kalanggaman.jpg" : "$SRC/kalanggaman-island_1440.jpg",
    'aerial'        => "$SRC/jc-gellidon-80GJUuOhXgA-unsplash.jpg",
    'sunset'        => "$SRC/chris-kursikowski-AGpzugP0SeQ-unsplash.jpg",
];
foreach ($general as $name => $f) {
    $widths = in_array($name, $heroKeys, true) ? $HERO_WIDTHS : $WIDTHS;
    if (is_file($f) && emit($f, "$OUT/general/$name", $widths)) {
        $manifest['general'][$name] = "general/$name";
        echo "  $name\n";
    }
}
// Logo (transparent) — single size.
if (is_file("$SRC/logo.png")) {
    emit("$SRC/logo.png", "$OUT/general/logo", ['full' => 520]);
    echo "  logo\n";
}

echo "== Services (reuse island/activity imagery) ==\n";
// Map service slugs to a general image; admins can replace later.
$serviceImg = [
    'kalanggaman-island-hopping' => "$SRC/13-kalanggaman-island-secondary-banner.jpg",
    'leyte-island-tour'          => "$SRC/kalanggaman-island_1440.jpg",
    'scuba-diving'               => "$SRC/beach.jpg",
    'water-sports'               => "$SRC/jc-gellidon-80GJUuOhXgA-unsplash.jpg",
    'airport-transfer'           => "$SRC/20180309-143032-largejpg.jpg",
    'car-rental'                 => "$SRC/chris-kursikowski-AGpzugP0SeQ-unsplash.jpg",
];
foreach ($serviceImg as $slug => $f) {
    if (is_file($f) && emit($f, "$OUT/services/$slug", $WIDTHS)) {
        $manifest['services'][$slug] = "services/$slug";
        echo "  $slug\n";
    }
}

echo "== Offers (one photo per promotion) ==\n";
// Drop a stock (e.g. Unsplash) JPG per offer at $SRC/offers/<slug>.jpg.
// Missing files are skipped and the offer falls back to the beach image.
$manifest['offers'] = [];
$offerSlugs = ['early-bird', 'stay-3-pay-2', 'summer-splash'];
foreach ($offerSlugs as $slug) {
    $f = "$SRC/offers/$slug.jpg";
    if (is_file($f) && emit($f, "$OUT/offers/$slug", $WIDTHS)) {
        $manifest['offers'][$slug] = "offers/$slug";
        echo "  $slug\n";
    } else {
        echo "  (missing) offers/$slug.jpg\n";
    }
}

file_put_contents(__DIR__ . '/../storage/media-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
echo "\nManifest written: storage/media-manifest.json\n";
