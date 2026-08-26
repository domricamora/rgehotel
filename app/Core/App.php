<?php
namespace App\Core;

/**
 * Application container / bootstrap.
 */
class App
{
    private static array $configCache = [];
    private static ?Router $router = null;

    public static function config(): array
    {
        if (empty(self::$configCache)) {
            self::$configCache = require dirname(__DIR__, 2) . '/config/config.php';
        }
        return self::$configCache;
    }

    public static function boot(): void
    {
        $cfg = self::config();
        date_default_timezone_set($cfg['app']['timezone'] ?? 'UTC');

        if ($cfg['app']['debug'] ?? false) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name($cfg['security']['session_name'] ?? 'rge_session');
            session_start();
        }
    }

    public static function router(): Router
    {
        if (self::$router === null) {
            self::$router = new Router();
        }
        return self::$router;
    }

    public static function run(): void
    {
        self::forceHttps();
        self::boot();
        $routes = require dirname(__DIR__, 2) . '/app/routes.php';
        $routes(self::router());
        self::router()->dispatch();
    }

    /**
     * Canonicalize to HTTPS on public hosts (SEO + security).
     * 301-redirects http -> https; sets HSTS on secure responses.
     * Skips local dev (localhost / 127.* / CLI dev server) so WAMP keeps working.
     */
    private static function forceHttps(): void
    {
        if (PHP_SAPI === 'cli') return;

        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host === '' || str_contains($host, 'localhost') || str_starts_with($host, '127.')) {
            return;
        }

        $canonicalHost = parse_url((string) (self::config()['app']['url'] ?? ''), PHP_URL_HOST);
        if ($canonicalHost && strcasecmp($host, 'www.' . $canonicalHost) === 0) {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: https://' . $canonicalHost . $uri, true, 301);
            exit;
        }

        $proto  = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $secure = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443)
            || $proto === 'https';

        if (!$secure) {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: https://' . $host . $uri, true, 301);
            exit;
        }

        // Already on HTTPS — instruct browsers to stick with it.
        header('Strict-Transport-Security: max-age=31536000');
    }
}
