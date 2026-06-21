# RGE Hotel & Restaurant Management System

A chic, modern, island-themed hotel website with an online booking engine, sandbox
payment processing (Xendit + PayPal), and a full role-based management backend.

Beachfront hotel in **Palompon, Leyte** — gateway to **Kalanggaman Island**.

## Stack
- **PHP 8.3** (no framework — small in-house MVC), **SQLite** (PDO)
- Vanilla **CSS/JS**, all libraries/fonts/icons **self-hosted** (no CDNs)
- Images normalized to **WebP** (responsive `thumb`/`full`)

## Layout
```
index.php            front controller        app/Core         framework (Router, DB, Auth, View)
.htaccess            routing + security       app/Controllers  Public/ + Admin/
config/config.php    config (+ config.local.php overrides)    app/Models  data access
database/schema.sql  schema                   app/Views        public/ admin/ layouts/ partials/
scripts/             migrate, seed, image-normalize, dev server
assets/              css js fonts img(webp) vendor
storage/             db/ (sqlite) logs/ uploads/   (gitignored)
coming-soon/         standalone holding page (deployed live)
```

## Setup (local / WAMP)
```bash
PHP=C:/wamp64/bin/php/php8.3.14/php.exe
$PHP scripts/migrate.php          # create tables
$PHP scripts/image-normalize.php  # source media -> WebP (reads C:/Users/Nick/Documents/rge)
$PHP scripts/seed.php             # seed roles, rooms, services, packages, offers, reviews, menu
```
Browse: `http://localhost/rgehotel/` · Admin: `http://localhost/rgehotel/admin`

Dev server (emulates .htaccess without Apache):
```bash
$PHP -S 127.0.0.1:8088 -t C:/wamp64/www C:/wamp64/www/rgehotel/scripts/serve-router.php
# -> http://127.0.0.1:8088/rgehotel/
```

## Admin accounts (dev — change before launch)
| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@rgehotel.com | password |
| Manager | manager@rgehotel.com | password |
| Front Desk | frontdesk@rgehotel.com | password |
| Housekeeping | housekeeping@rgehotel.com | password |
| Restaurant | restaurant@rgehotel.com | password |
| Accounting | accounting@rgehotel.com | password |

## Payments
Sandbox-ready. Add keys in `config/config.local.php` (gitignored):
```php
<?php return [
  'payments' => [
    'xendit' => ['secret_key' => 'xnd_development_...', 'webhook_token' => '...'],
    'paypal' => ['client_id' => '...', 'client_secret' => '...', 'mode' => 'sandbox'],
  ],
];
```
Until real keys are set, the pay page runs a **simulated** sandbox payment (no charge)
so the booking flow is fully testable. Webhooks: `/payment/xendit/webhook`,
PayPal return: `/payment/paypal/return`.

## Notes
- Restaurant page is **unpublished** by default (toggle in Admin → Settings / Restaurant).
- Room content & pricing are reasonable placeholders, fully editable in admin.
- Deploy target base path: set `base_path => ''` in `config.local.php` when serving at domain root.
