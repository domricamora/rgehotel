<?php
/**
 * RGE Hotel — application configuration.
 * Local overrides go in config.local.php (gitignored) and are merged over this.
 */

$config = [
    // Base URL path the app is served from. WAMP serves this project at /rgehotel/.
    // On the production root deploy, set this to '' (empty).
    'base_path' => '/rgehotel',

    'app' => [
        'name'      => 'RGE Hotel',
        'tagline'   => 'Beachfront Escape · Kalanggaman Island, Leyte',
        'url'       => 'https://rgehotel.com',  // canonical absolute base (canonical/OG/sitemap)
        'env'       => 'local',           // local | production
        'debug'     => true,
        'timezone'  => 'Asia/Manila',
        'currency'  => 'PHP',
        'currency_symbol' => '₱',
        'locale'    => 'en_PH',
    ],

    'db' => [
        'path' => dirname(__DIR__) . '/storage/db/rge.sqlite',
    ],

    'hotel' => [
        'legal_name' => 'RGE Hotel',
        'email'      => 'info@rgehotel.com',
        'phone'      => '',               // TODO: confirm real number with client
        'address'    => 'Palompon, Leyte, Philippines',
        'locality'   => 'Palompon',
        'region'     => 'Leyte',
        'country'    => 'PH',
        'lat'        => 11.0500,
        'lng'        => 124.3900,
        'facebook'   => '',               // TODO
        'instagram'  => '',               // TODO
    ],

    // Feature flags — restaurant kept unpublished on the live server per spec.
    'features' => [
        'restaurant_published' => false,
    ],

    // Payment gateways — SANDBOX. Swap to live keys in config.local.php before launch.
    'payments' => [
        'paymongo' => [
            'enabled'              => true,
            'mode'                 => 'test',   // test | live — picks the te= / li= signature
            'secret_key'           => 'sk_test_REPLACE_ME',
            'webhook_secret'       => 'whsk_REPLACE_ME',
            // Must also be activated on the PayMongo account or create calls fail.
            'payment_method_types' => ['card', 'gcash', 'paymaya', 'grab_pay', 'qrph'],
        ],
        'paypal' => [
            'enabled'        => true,
            'mode'           => 'sandbox', // sandbox | live
            'client_id'      => 'REPLACE_ME_PAYPAL_CLIENT_ID',
            'client_secret'  => 'REPLACE_ME_PAYPAL_CLIENT_SECRET',
            'webhook_id'     => 'REPLACE_ME_PAYPAL_WEBHOOK_ID', // from PayPal dashboard / API
        ],
    ],

    'mail' => [
        'from_name'  => 'RGE Hotel',
        'from_email' => 'info@rgehotel.com',
        // Local dev: PHP mail() is typically unconfigured; messages are logged instead.
        // On production set log_only=false in config.local.php and fill in smtp.* below.
        'log_only'   => true,
        'log_path'   => dirname(__DIR__) . '/storage/logs/mail.log',
        // SMTP (PHPMailer). Leave host empty to fall back to PHP mail().
        // Put real credentials in config.local.php, never here.
        'smtp' => [
            'host'       => '',          // e.g. mail.rgehotel.com
            'port'       => 587,
            'username'   => '',          // e.g. info@rgehotel.com
            'password'   => '',
            'encryption' => 'tls',       // tls | ssl | none
        ],
    ],

    'security' => [
        'session_name' => 'rge_session',
    ],
];

// Merge local overrides if present.
$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

return $config;
