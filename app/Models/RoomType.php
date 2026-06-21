<?php
namespace App\Models;

use App\Core\Database;

class RoomType
{
    private static function db(): Database { return Database::instance(); }

    /** All published room types with cover photo + rating, ordered. */
    public static function published(): array
    {
        $rows = self::db()->all(
            "SELECT rt.*, rp.filename AS cover
             FROM room_types rt
             LEFT JOIN room_photos rp ON rp.room_type_id = rt.id AND rp.is_cover = 1
             WHERE rt.is_published = 1
             ORDER BY rt.sort_order, rt.id"
        );
        return array_map([self::class, 'decorate'], $rows);
    }

    public static function featured(int $limit = 3): array
    {
        $rows = self::db()->all(
            "SELECT rt.*, rp.filename AS cover
             FROM room_types rt
             LEFT JOIN room_photos rp ON rp.room_type_id = rt.id AND rp.is_cover = 1
             WHERE rt.is_published = 1 AND rt.is_featured = 1
             ORDER BY rt.sort_order LIMIT ?",
            [$limit]
        );
        return array_map([self::class, 'decorate'], $rows);
    }

    public static function findBySlug(string $slug): ?array
    {
        $row = self::db()->first('SELECT * FROM room_types WHERE slug = ? AND is_published = 1', [$slug]);
        if (!$row) return null;
        return self::decorate($row);
    }

    public static function find(int $id): ?array
    {
        $row = self::db()->first('SELECT * FROM room_types WHERE id = ?', [$id]);
        return $row ? self::decorate($row) : null;
    }

    public static function photos(int $roomTypeId): array
    {
        return self::db()->all('SELECT * FROM room_photos WHERE room_type_id = ? ORDER BY is_cover DESC, sort_order', [$roomTypeId]);
    }

    public static function amenities(int $roomTypeId): array
    {
        return self::db()->all(
            'SELECT a.* FROM room_type_amenities rta
             JOIN amenities a ON a.id = rta.amenity_id
             WHERE rta.room_type_id = ? ORDER BY a.id',
            [$roomTypeId]
        );
    }

    /** Add rating summary + cover convenience. */
    private static function decorate(array $row): array
    {
        $r = self::db()->first(
            "SELECT COUNT(*) c, AVG(rating) avg FROM reviews
             WHERE is_approved = 1 AND ((subject_type='room_type' AND subject_id = ?) )",
            [$row['id']]
        );
        $row['review_count'] = (int) ($r['c'] ?? 0);
        $row['avg_rating'] = $r['avg'] ? round((float) $r['avg'], 1) : null;
        if (!isset($row['cover'])) {
            $row['cover'] = self::db()->scalar('SELECT filename FROM room_photos WHERE room_type_id = ? ORDER BY is_cover DESC, sort_order LIMIT 1', [$row['id']]);
        }
        return $row;
    }

    /** Related packages for a room type. */
    public static function packages(int $roomTypeId): array
    {
        return self::db()->all(
            'SELECT p.* FROM package_room_types prt
             JOIN packages p ON p.id = prt.package_id
             WHERE prt.room_type_id = ? AND p.is_published = 1 ORDER BY p.sort_order',
            [$roomTypeId]
        );
    }

    /** Active offers applicable to a room type (or global offers with no room link). */
    public static function offers(int $roomTypeId): array
    {
        return self::db()->all(
            "SELECT DISTINCT o.* FROM offers o
             LEFT JOIN offer_room_types ort ON ort.offer_id = o.id
             WHERE o.is_published = 1
               AND (ort.room_type_id = ? OR NOT EXISTS (SELECT 1 FROM offer_room_types x WHERE x.offer_id = o.id))
             ORDER BY o.sort_order",
            [$roomTypeId]
        );
    }
}
