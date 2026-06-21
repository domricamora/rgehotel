<?php
/**
 * RGE Hotel — front controller.
 * All non-file requests are routed here via .htaccess.
 */

// PSR-4-ish autoloader for the App\ namespace.
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require $file;
});

// Helpers (procedural).
require __DIR__ . '/app/Core/helpers.php';

use App\Core\App;

try {
    App::run();
} catch (\Throwable $e) {
    logger('UNCAUGHT: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(), 'error');
    $debug = (bool) (App::config()['app']['debug'] ?? false);
    http_response_code(500);
    if ($debug) {
        echo '<pre style="padding:2rem;font:14px/1.5 monospace;color:#b00">';
        echo e($e->getMessage()) . "\n\n" . e($e->getTraceAsString());
        echo '</pre>';
    } else {
        $view = __DIR__ . '/app/Views/errors/500.php';
        if (is_file($view)) require $view; else echo 'Something went wrong.';
    }
}
