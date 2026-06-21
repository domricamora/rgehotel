<?php
/**
 * Idempotent accounting setup — safe to run on production (no data wiped).
 * Adds expense categories, accounting permissions + role grants, and VAT settings.
 * Run: php scripts/seed-accounting.php
 */
require __DIR__ . '/../app/Core/helpers.php';
spl_autoload_register(function ($c) {
    if (!str_starts_with($c, 'App\\')) return;
    $f = __DIR__ . '/../app/' . str_replace('\\', '/', substr($c, 4)) . '.php';
    if (is_file($f)) require $f;
});
use App\Core\Database;
$db = Database::instance();

// 1) Expense categories
$cats = [
    'utilities' => 'Utilities (power, water, internet)',
    'salaries' => 'Salaries & Wages',
    'supplies' => 'Supplies & Consumables',
    'maintenance' => 'Repairs & Maintenance',
    'food-beverage' => 'Food & Beverage Cost',
    'marketing' => 'Marketing & Advertising',
    'commissions' => 'OTA / Booking Commissions',
    'transport' => 'Transport & Fuel',
    'taxes-fees' => 'Taxes, Permits & Fees',
    'other' => 'Other',
];
foreach ($cats as $slug => $name) {
    $db->run('INSERT OR IGNORE INTO expense_categories (slug, name) VALUES (?, ?)', [$slug, $name]);
}

// 2) Accounting permissions
$perms = [
    'accounting.view'   => ['View accounting', 'finance'],
    'accounting.manage' => ['Manage accounting (expenses, refunds)', 'finance'],
];
foreach ($perms as $slug => [$name, $grp]) {
    $db->run('INSERT OR IGNORE INTO permissions (slug, name, grp) VALUES (?, ?, ?)', [$slug, $name, $grp]);
}
// Grant to roles
$grant = ['super_admin' => ['accounting.view','accounting.manage'],
          'manager'     => ['accounting.view','accounting.manage'],
          'accounting'  => ['accounting.view','accounting.manage']];
foreach ($grant as $role => $slugs) {
    $rid = $db->scalar('SELECT id FROM roles WHERE slug = ?', [$role]);
    if (!$rid) continue;
    foreach ($slugs as $ps) {
        $pid = $db->scalar('SELECT id FROM permissions WHERE slug = ?', [$ps]);
        if ($pid) $db->run('INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)', [$rid, $pid]);
    }
}

// 3) VAT settings
$settings = [
    ['vat_rate', '12', 'string', 'finance'],
    ['vat_inclusive', '1', 'bool', 'finance'],
    ['business_tin', '', 'string', 'finance'],
];
foreach ($settings as [$k, $v, $t, $g]) {
    $db->run('INSERT OR IGNORE INTO settings (key, value, type, grp) VALUES (?, ?, ?, ?)', [$k, $v, $t, $g]);
}

echo "Accounting setup complete:\n";
echo "  expense categories: " . $db->scalar('SELECT COUNT(*) FROM expense_categories') . "\n";
echo "  accounting perms:   " . $db->scalar("SELECT COUNT(*) FROM permissions WHERE slug LIKE 'accounting.%'") . "\n";
echo "  vat_rate:           " . $db->scalar("SELECT value FROM settings WHERE key='vat_rate'") . "%\n";
