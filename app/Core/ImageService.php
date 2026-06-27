<?php
namespace App\Core;

/**
 * Image ingestion for admin uploads. Converts an uploaded image into the two
 * web-optimized WebP sizes the site serves (-thumb 600w, -full 1400w) under
 * assets/img/<base>, mirroring scripts/image-normalize.php so img_url()/img_tag()
 * resolve them transparently. Uploaded originals live under assets/img/uploads/
 * (gitignored) so they survive deploys without needing to be committed.
 */
class ImageService
{
    private const SIZES = ['thumb' => 600, 'full' => 1400];

    /** Is the GD + WebP toolchain available on this server? */
    public static function available(): bool
    {
        return function_exists('imagewebp')
            && function_exists('imagecreatetruecolor')
            && function_exists('getimagesize');
    }

    private static function imgRoot(): string
    {
        return dirname(__DIR__, 2) . '/assets/img/';
    }

    /** A collision-free base path under uploads/, e.g. "uploads/rooms/20260627-110501-a1b2c3d4". */
    public static function uniqueBase(string $prefix): string
    {
        $prefix = trim(preg_replace('#[^a-z0-9/_-]#i', '', $prefix), '/');
        return 'uploads/' . $prefix . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));
    }

    /**
     * Ingest a single $_FILES entry into assets/img/<base>{-thumb,-full}.webp.
     * Returns the base path on success, or null on any failure.
     */
    public static function ingestUpload(array $file, string $base): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;
        return self::process($file['tmp_name'], $base);
    }

    /** Convert an image at $srcPath into the two WebP sizes under assets/img/<base>. */
    public static function process(string $srcPath, string $base): ?string
    {
        if (!self::available()) {
            logger('ImageService: GD/WebP unavailable — cannot process upload', 'app');
            return null;
        }
        $info = @getimagesize($srcPath);
        if (!$info) return null;
        $im = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($srcPath),
            IMAGETYPE_PNG  => @imagecreatefrompng($srcPath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($srcPath),
            IMAGETYPE_GIF  => @imagecreatefromgif($srcPath),
            default        => null,
        };
        if (!$im) return null;

        $w = imagesx($im);
        $h = imagesy($im);
        $out = self::imgRoot() . $base;
        @mkdir(dirname($out), 0777, true);

        foreach (self::SIZES as $suffix => $maxW) {
            $tw = min($maxW, $w);
            $th = max(1, (int) round($h * ($tw / $w)));
            $dst = imagecreatetruecolor($tw, $th);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $tw, $th, $transparent);
            imagecopyresampled($dst, $im, 0, 0, 0, 0, $tw, $th, $w, $h);
            imagewebp($dst, "$out-$suffix.webp", $suffix === 'thumb' ? 80 : 82);
            imagedestroy($dst);
        }
        imagedestroy($im);
        return $base;
    }

    /** Delete both WebP sizes for a base path. Only ever touches files under uploads/. */
    public static function deleteBase(?string $base): void
    {
        if (!$base || !str_starts_with($base, 'uploads/')) return; // never delete seeded/shared assets
        foreach (array_keys(self::SIZES) as $suffix) {
            $f = self::imgRoot() . $base . "-$suffix.webp";
            if (is_file($f)) @unlink($f);
        }
    }
}
