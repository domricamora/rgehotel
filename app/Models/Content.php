<?php
namespace App\Models;

use App\Core\Database;

/**
 * Shared read helpers for services, packages, offers, reviews, restaurant, pages.
 */
class Content
{
    private static function db(): Database { return Database::instance(); }

    /* ---------------- Services ---------------- */
    public static function services(?string $category = null): array
    {
        $sql = 'SELECT * FROM services WHERE is_published = 1';
        $params = [];
        if ($category) { $sql .= ' AND category = ?'; $params[] = $category; }
        $sql .= ' ORDER BY sort_order, id';
        return self::db()->all($sql, $params);
    }
    public static function featuredServices(int $limit = 3): array
    {
        return self::db()->all('SELECT * FROM services WHERE is_published = 1 AND is_featured = 1 ORDER BY sort_order LIMIT ?', [$limit]);
    }
    public static function service(string $slug): ?array
    {
        return self::db()->first('SELECT * FROM services WHERE slug = ? AND is_published = 1', [$slug]);
    }

    /* ---------------- Packages ---------------- */
    public static function packages(): array
    {
        return self::db()->all('SELECT * FROM packages WHERE is_published = 1 ORDER BY sort_order, id');
    }
    public static function featuredPackages(int $limit = 3): array
    {
        return self::db()->all('SELECT * FROM packages WHERE is_published = 1 AND is_featured = 1 ORDER BY sort_order LIMIT ?', [$limit]);
    }
    public static function package(string $slug): ?array
    {
        return self::db()->first('SELECT * FROM packages WHERE slug = ? AND is_published = 1', [$slug]);
    }
    public static function packageRoomTypes(int $packageId): array
    {
        return self::db()->all(
            'SELECT rt.*, rp.filename AS cover FROM package_room_types prt
             JOIN room_types rt ON rt.id = prt.room_type_id
             LEFT JOIN room_photos rp ON rp.room_type_id = rt.id AND rp.is_cover = 1
             WHERE prt.package_id = ? AND rt.is_published = 1', [$packageId]);
    }
    public static function packageServices(int $packageId): array
    {
        return self::db()->all(
            'SELECT s.* FROM package_services ps JOIN services s ON s.id = ps.service_id
             WHERE ps.package_id = ?', [$packageId]);
    }

    /* ---------------- Offers ---------------- */
    public static function offers(): array
    {
        return self::db()->all('SELECT * FROM offers WHERE is_published = 1 ORDER BY sort_order, id');
    }
    public static function featuredOffers(int $limit = 3): array
    {
        return self::db()->all('SELECT * FROM offers WHERE is_published = 1 AND is_featured = 1 ORDER BY sort_order LIMIT ?', [$limit]);
    }
    public static function offerByCode(string $code): ?array
    {
        return self::db()->first("SELECT * FROM offers WHERE UPPER(code) = UPPER(?) AND is_published = 1
            AND (ends_at IS NULL OR date(ends_at) >= date('now'))", [$code]);
    }

    /* ---------------- Reviews ---------------- */
    public static function reviews(string $type = null, int $id = null, int $limit = 50): array
    {
        $sql = 'SELECT * FROM reviews WHERE is_approved = 1';
        $params = [];
        if ($type) { $sql .= ' AND subject_type = ?'; $params[] = $type; if ($id) { $sql .= ' AND subject_id = ?'; $params[] = $id; } }
        $sql .= ' ORDER BY created_at DESC LIMIT ?'; $params[] = $limit;
        return self::db()->all($sql, $params);
    }
    public static function ratingSummary(): array
    {
        $r = self::db()->first('SELECT COUNT(*) c, AVG(rating) avg FROM reviews WHERE is_approved = 1');
        return ['count' => (int)($r['c'] ?? 0), 'avg' => $r['avg'] ? round((float)$r['avg'],1) : null];
    }

    /* ---------------- Restaurant ---------------- */
    public static function menu(): array
    {
        $cats = self::db()->all('SELECT * FROM menu_categories WHERE is_published = 1 ORDER BY sort_order, id');
        foreach ($cats as &$c) {
            $c['items'] = self::db()->all('SELECT * FROM menu_items WHERE category_id = ? AND is_available = 1 ORDER BY sort_order, id', [$c['id']]);
        }
        return $cats;
    }

    /* ---------------- Pages ---------------- */
    public static function page(string $slug): ?array
    {
        return self::db()->first('SELECT * FROM pages WHERE slug = ? AND is_published = 1', [$slug]);
    }
}
