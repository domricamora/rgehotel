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
        self::boot();
        $routes = require dirname(__DIR__, 2) . '/app/routes.php';
        $routes(self::router());
        self::router()->dispatch();
    }
}
