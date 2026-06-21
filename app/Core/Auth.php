<?php
namespace App\Core;

/**
 * Authentication + role/permission checks.
 */
class Auth
{
    private static ?array $user = null;

    public static function attempt(string $email, string $password): bool
    {
        $db = Database::instance();
        $user = $db->first(
            'SELECT * FROM users WHERE email = ? AND is_active = 1',
            [strtolower(trim($email))]
        );
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        // Refresh hash if algorithm changed.
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $db->update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], ['id' => $user['id']]);
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $db->update('users', ['last_login_at' => date('c')], ['id' => $user['id']]);
        self::$user = null;
        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id']);
        self::$user = null;
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$user !== null) return self::$user;
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) return null;
        $db = Database::instance();
        $user = $db->first(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? AND u.is_active = 1',
            [$id]
        );
        self::$user = $user ?: null;
        return self::$user;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['id'] : null;
    }

    public static function role(): ?string
    {
        $u = self::user();
        return $u['role_slug'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'super_admin';
    }

    /** Does the current user have a given permission slug? super_admin has all. */
    public static function can(string $permission): bool
    {
        $u = self::user();
        if (!$u) return false;
        if ($u['role_slug'] === 'super_admin') return true;
        $db = Database::instance();
        $count = $db->scalar(
            'SELECT COUNT(*) FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = ? AND p.slug = ?',
            [$u['role_id'], $permission]
        );
        return (int) $count > 0;
    }

    /** Returns the set of permission slugs for the current user. */
    public static function permissions(): array
    {
        $u = self::user();
        if (!$u) return [];
        $db = Database::instance();
        if ($u['role_slug'] === 'super_admin') {
            return array_column($db->all('SELECT slug FROM permissions'), 'slug');
        }
        return array_column($db->all(
            'SELECT p.slug FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = ?',
            [$u['role_id']]
        ), 'slug');
    }
}
