<?php
namespace App\Models;

use App\Core\Database;

class Setting
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            $rows = Database::instance()->all('SELECT key, value FROM settings');
            self::$cache = array_column($rows, 'value', 'key');
        }
        return self::$cache;
    }

    public static function get(string $key, $default = null)
    {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        $db = Database::instance();
        $exists = $db->scalar('SELECT COUNT(*) FROM settings WHERE key = ?', [$key]);
        if ($exists) {
            $db->update('settings', ['value' => $value], ['key' => $key]);
        } else {
            $db->insert('settings', ['key' => $key, 'value' => $value]);
        }
        self::$cache = null;
    }
}
