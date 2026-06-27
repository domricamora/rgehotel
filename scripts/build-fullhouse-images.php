<?php
/**
 * Build web-optimized WebP for the Full House gallery from the high-res interior
 * shoot in the RGE media folder. Produces -thumb (600w) and -full (1400w) at
 * assets/img/rooms/full-house/1..N. Mirrors scripts/image-normalize.php.
 *
 *   php scripts/build-fullhouse-images.php
 */
ini_set('memory_limit', '2048M');

$SRC = 'C:/Users/Nick/Documents/rge/amenities';
$OUT = __DIR__ . '/../assets/img/rooms/full-house';
$WIDTHS = ['thumb' => 600, 'full' => 1400];

// Cover first (the photo the owner pointed to), then the rest of the same shoot.
$sources = [
    '594252742_870047382378412_8457878576009081580_n.jpg',
    '594013300_1184529956550958_5955642622108561993_n.jpg',
    '594326397_2949634535228873_6749256334121029165_n.jpg',
    '594387564_785741791144138_4154248277123503419_n.jpg',
    '594451324_877650754639168_5008893104074971605_n.jpg',
    '594486203_1288022046685656_5227400726937403558_n.jpg',
];

function emit(string $srcPath, string $outBase, array $widths): bool {
    $info = @getimagesize($srcPath);
    if (!$info) { fwrite(STDERR, "  skip (unreadable): $srcPath\n"); return false; }
    $im = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($srcPath),
        IMAGETYPE_PNG  => @imagecreatefrompng($srcPath),
        IMAGETYPE_WEBP => @imagecreatefromwebp($srcPath),
        default        => null,
    };
    if (!$im) { fwrite(STDERR, "  skip (decode failed): $srcPath\n"); return false; }
    $w = imagesx($im); $h = imagesy($im);
    @mkdir(dirname($outBase), 0777, true);
    foreach ($widths as $suffix => $maxW) {
        $tw = min($maxW, $w);
        $th = (int) round($h * ($tw / $w));
        $dst = imagecreatetruecolor($tw, $th);
        imagecopyresampled($dst, $im, 0, 0, 0, 0, $tw, $th, $w, $h);
        imagewebp($dst, "$outBase-$suffix.webp", $suffix === 'thumb' ? 80 : 82);
        imagedestroy($dst);
    }
    imagedestroy($im);
    return true;
}

$i = 1;
foreach ($sources as $name) {
    $f = "$SRC/$name";
    if (emit($f, "$OUT/$i", $WIDTHS)) { echo "  full-house/$i  <- $name\n"; $i++; }
}
echo "\nDone — " . ($i - 1) . " Full House photos at assets/img/rooms/full-house/\n";
