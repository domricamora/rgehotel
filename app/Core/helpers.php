<?php
/**
 * Global helper functions. Loaded once at bootstrap.
 */

use App\Core\App;

require_once __DIR__ . '/Icons.php';

if (!function_exists('config')) {
    /** Dot-notation access to config: config('app.name'). */
    function config(string $key = null, $default = null)
    {
        $cfg = App::config();
        if ($key === null) return $cfg;
        $segments = explode('.', $key);
        $value = $cfg;
        foreach ($segments as $seg) {
            if (is_array($value) && array_key_exists($seg, $value)) {
                $value = $value[$seg];
            } else {
                return $default;
            }
        }
        return $value;
    }
}

if (!function_exists('e')) {
    /** HTML-escape. */
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('base_path_url')) {
    function base_path_url(): string
    {
        return rtrim((string) config('base_path', ''), '/');
    }
}

if (!function_exists('url')) {
    /** Build an app URL respecting the base path. url('/rooms') */
    function url(string $path = '/'): string
    {
        if (preg_match('#^https?://#', $path)) return $path;
        return base_path_url() . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /** URL for a file under /assets. asset('css/app.css') */
    function asset(string $path): string
    {
        $full = dirname(__DIR__, 2) . '/assets/' . ltrim($path, '/');
        $v = is_file($full) ? '?v=' . filemtime($full) : '';
        return base_path_url() . '/assets/' . ltrim($path, '/') . $v;
    }
}

if (!function_exists('uploads_url')) {
    function uploads_url(string $path): string
    {
        return base_path_url() . '/storage/uploads/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = '')
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    /** Set or get a one-time flash message. */
    function flash(string $key, ?string $message = null)
    {
        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;
            return null;
        }
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(): bool
    {
        $token = $_POST['_csrf'] ?? '';
        return is_string($token) && !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
    }
}

if (!function_exists('money')) {
    /** Format a peso amount. */
    function money($amount, bool $withSymbol = true): string
    {
        $s = number_format((float) $amount, 0);
        return $withSymbol ? config('app.currency_symbol', '₱') . $s : $s;
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = trim($text, '-');
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text) ?: $text;
        $text = strtolower(preg_replace('~[^-\w]+~', '', $text));
        return $text ?: 'n-a';
    }
}

if (!function_exists('img_url')) {
    /** URL for a normalized image base, e.g. img_url('rooms/suite/1','full'). */
    function img_url(?string $base, string $size = 'full'): string
    {
        if (!$base) return asset('img/general/beach-' . $size . '.webp');
        return asset("img/{$base}-{$size}.webp");
    }
}

if (!function_exists('img_tag')) {
    /** Responsive <img> with thumb/full srcset and lazy loading. */
    function img_tag(?string $base, string $alt = '', string $class = '', string $sizes = '(max-width: 700px) 100vw, 50vw', bool $eager = false): string
    {
        $thumb = img_url($base, 'thumb');
        $full  = img_url($base, 'full');
        $cls = $class ? ' class="' . e($class) . '"' : '';
        $loading = $eager ? 'eager' : 'lazy';
        $priority = $eager ? ' fetchpriority="high"' : '';
        return '<img' . $cls . ' src="' . e($full) . '" '
            . 'srcset="' . e($thumb) . ' 600w, ' . e($full) . ' 1400w" '
            . 'sizes="' . e($sizes) . '" alt="' . e($alt) . '" loading="' . $loading . '"' . $priority . ' decoding="async">';
    }
}

if (!function_exists('icon')) {
    /** Inline self-hosted SVG icon. */
    function icon(string $name, string $class = '', int $size = 24): string
    {
        return \App\Core\Icons::svg($name, $class, $size);
    }
}

if (!function_exists('badge')) {
    /** Render a coloured status badge. */
    function badge(string $status): string
    {
        $map = [
            'paid' => 'green', 'confirmed' => 'green', 'checked_out' => 'gray', 'available' => 'green',
            'pending' => 'amber', 'unpaid' => 'amber', 'partial' => 'amber', 'cleaning' => 'amber',
            'cancelled' => 'red', 'failed' => 'red', 'maintenance' => 'red',
            'checked_in' => 'blue', 'occupied' => 'blue', 'refunded' => 'gray',
        ];
        $cls = $map[$status] ?? 'gray';
        return '<span class="badge badge-' . $cls . '">' . e(ucwords(str_replace('_', ' ', $status))) . '</span>';
    }
}

if (!function_exists('partial')) {
    /** Render a view partial to string. */
    function partial(string $view, array $data = []): string
    {
        return \App\Core\View::partial($view, $data);
    }
}

if (!function_exists('stars')) {
    /** Render star rating markup for a 0–5 value. */
    function stars($rating, int $size = 16): string
    {
        $rating = (float) $rating;
        $out = '<span class="rating" aria-label="' . e(round($rating,1)) . ' out of 5">';
        for ($i = 1; $i <= 5; $i++) {
            $on = $i <= round($rating);
            $out .= '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="' . ($on ? 'currentColor' : 'none')
                . '" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
        }
        return $out . '</span>';
    }
}

if (!function_exists('logger')) {
    function logger(string $message, string $channel = 'app'): void
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $line = '[' . date('c') . "] $message" . PHP_EOL;
        file_put_contents("$dir/$channel.log", $line, FILE_APPEND | LOCK_EX);
    }
}
