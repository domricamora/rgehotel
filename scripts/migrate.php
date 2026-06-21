<?php
/**
 * Create / update the SQLite schema. Run:
 *   php scripts/migrate.php
 */
require __DIR__ . '/../app/Core/helpers.php';
spl_autoload_register(function ($class) {
    if (!str_starts_with($class, 'App\\')) return;
    $f = __DIR__ . '/../app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($f)) require $f;
});

use App\Core\Database;

$db = Database::instance();
$sql = file_get_contents(__DIR__ . '/../database/schema.sql');
$db->pdo()->exec($sql);

$tables = $db->all("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
echo "Migration complete. Tables (" . count($tables) . "):\n";
foreach ($tables as $t) echo "  - {$t['name']}\n";
