<?php
/**
 * Import the current RGE Hotel photography into the existing responsive WebP slots.
 *
 * Usage:
 *   php scripts/import-rge-hotel-photos.php
 *
 * The selected images are intentionally grouped by their visible bed layout. This
 * preserves the URLs already stored in the database, so it is safe for existing
 * bookings and content records.
 */
declare(strict_types=1);

ini_set('memory_limit', '1024M');

$source = 'C:/Users/Nick/Documents/projects/rge/recent photos/RGE HOTEL AND RESORT';
$output = __DIR__ . '/../assets/img';
$widths = ['thumb' => 600, 'full' => 1400];
// 1600px keeps the high-resolution camera original crisp while producing a
// dependable, fast-to-encode hero asset on the local PHP/GD installation.
$heroWidths = ['thumb' => 900, 'full' => 1600];

function importPhoto(string $source, string $output, string $filename, string $base, array $widths): void
{
    $path = "$source/$filename";
    if (!is_file($path)) {
        throw new RuntimeException("Missing source photo: $path");
    }
    $image = @imagecreatefromjpeg($path);
    if (!$image) {
        throw new RuntimeException("Unreadable source photo: $path");
    }
    $sourceWidth = imagesx($image);
    $sourceHeight = imagesy($image);
    $targetBase = "$output/$base";
    if (!is_dir(dirname($targetBase))) mkdir(dirname($targetBase), 0777, true);

    foreach ($widths as $suffix => $maxWidth) {
        $targetWidth = min($maxWidth, $sourceWidth);
        $targetHeight = (int) round($sourceHeight * ($targetWidth / $sourceWidth));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($target, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
        imagewebp($target, "$targetBase-$suffix.webp", $suffix === 'thumb' ? 80 : 82);
        imagedestroy($target);
    }
    imagedestroy($image);
    echo "Imported $filename -> $base\n";
}

if (($argv[1] ?? '') === 'hero') {
    importPhoto($source, $output, 'IMG_4203.jpg', 'general/hero-island', $heroWidths);
    exit(0);
}

// Room galleries.  The selected groups visibly match their stated bed layouts.
$rooms = [
    'twin-room'       => ['IMG_4030.jpg', 'IMG_4034.jpg', 'IMG_4035.jpg', 'IMG_4036.jpg', 'IMG_4047.jpg'], // two single beds
    'double-room'     => ['IMG_4303.jpg', 'IMG_4304.jpg', 'IMG_4307.jpg', 'IMG_4311.jpg'], // one queen bed
    'double-or-twin'  => ['IMG_4082.jpg', 'IMG_4084.jpg', 'IMG_4086.jpg', 'IMG_4091.jpg'], // twin configuration
    'triple-room'     => ['IMG_4211.jpg', 'IMG_4212.jpg', 'IMG_4218.jpg', 'IMG_4219.jpg', 'IMG_4223.jpg'], // three beds
    'family-room'     => ['IMG_4270.jpg', 'IMG_4271.jpg', 'IMG_4276.jpg', 'IMG_4278.jpg'], // family layout
    'suite'           => ['IMG_4158.jpg', 'IMG_4163.jpg', 'IMG_4164.jpg', 'IMG_4165.jpg'], // larger queen room
    // The supplied set does not include a clearly identifiable 6- or 8-bed room,
    // so the Barkada galleries are deliberately left unchanged.
    'full-house'      => ['IMG_3889.jpg', 'IMG_3895.jpg', 'IMG_3896.jpg', 'IMG_3901.jpg', 'IMG_3924.jpg', 'IMG_3960.jpg'],
];

foreach ($rooms as $slug => $photos) {
    foreach ($photos as $index => $filename) {
        importPhoto($source, $output, $filename, "rooms/$slug/" . ($index + 1), $widths);
    }
}

// Hotel amenities and shared spaces, retained for the amenity-media set.
$amenities = [
    'IMG_4323.jpg', // reception
    'IMG_3889.jpg', // lounge
    'IMG_3895.jpg', // shared kitchen
    'IMG_3924.jpg', // bathroom
    'IMG_4194.jpg', // guest refreshment tray
    'IMG_4174.jpg', // hotel corridor
    'IMG_4203.jpg', // exterior
    'IMG_4287.jpg', // bathroom
    'IMG_4290.jpg', // guest amenity kit
    'IMG_4108.jpg', // hot shower
    'IMG_4319.jpg', // reception desk
    'IMG_3960.jpg', // hotel exterior
];
foreach ($amenities as $index => $filename) {
    importPhoto($source, $output, $filename, 'amenities/' . ($index + 1), $widths);
}

// Shared areas used by public page heroes: exterior for the home page, actual
// reception for the About page, and an additional exterior detail for generic use.
importPhoto($source, $output, 'IMG_4203.jpg', 'general/hero-island', $heroWidths);
importPhoto($source, $output, 'IMG_4323.jpg', 'general/about', $heroWidths);
importPhoto($source, $output, 'IMG_4267.jpg', 'general/aerial', $widths);

// Keep the media manifest aligned for installations seeded from scratch.
$manifestPath = __DIR__ . '/../storage/media-manifest.json';
$manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
foreach ($rooms as $slug => $photos) {
    $manifest['rooms'][$slug] = array_map(static fn (int $i): string => "rooms/$slug/$i", range(1, count($photos)));
}
$manifest['amenities'] = array_map(static fn (int $i): string => "amenities/$i", range(1, count($amenities)));
$manifest['general']['hero-island'] = 'general/hero-island';
$manifest['general']['about'] = 'general/about';
$manifest['general']['aerial'] = 'general/aerial';
file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

echo "\nDone. Existing room-photo URLs now point to the current RGE Hotel photography.\n";
