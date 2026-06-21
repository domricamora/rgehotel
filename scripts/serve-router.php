<?php
/**
 * Router for PHP's built-in server to emulate the .htaccess front-controller.
 * Run from C:\wamp64\www as docroot:
 *   php -S 127.0.0.1:8088 -t C:/wamp64/www C:/wamp64/www/rgehotel/scripts/serve-router.php
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$docroot = $_SERVER['DOCUMENT_ROOT'];
$full = $docroot . $uri;

// Serve existing static files directly (assets, coming-soon, etc.)
if ($uri !== '/' && is_file($full)) {
    return false;
}

// Route /rgehotel/* (except real files) to the front controller.
if (preg_match('#^/rgehotel(/|$)#', $uri)) {
    $_SERVER['SCRIPT_NAME'] = '/rgehotel/index.php';
    require $docroot . '/rgehotel/index.php';
    return true;
}

return false;
