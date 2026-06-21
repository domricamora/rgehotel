<?php
namespace App\Models;

use App\Core\Database;

class Booking
{
    private static function db(): Database { return Database::instance(); }

    public const ACTIVE_STATUSES = ['pending', 'confirmed', 'checked_in'];

    /** Units already committed for a room type over an overlapping date range. */
    public static function bookedUnits(int $roomTypeId, string $in, string $out, ?int $excludeId = null): int
    {
        $sql = "SELECT COALESCE(SUM(rooms_count),0) FROM bookings
                WHERE room_type_id = ?
                  AND status IN ('pending','confirmed','checked_in')
                  AND NOT (date(check_out) <= date(?) OR date(check_in) >= date(?))";
        $params = [$roomTypeId, $in, $out];
        if ($excludeId) { $sql .= ' AND id != ?'; $params[] = $excludeId; }
        return (int) self::db()->scalar($sql, $params);
    }

    public static function availableUnits(int $roomTypeId, string $in, string $out): int
    {
        $total = (int) self::db()->scalar('SELECT total_units FROM room_types WHERE id = ?', [$roomTypeId]);
        return max(0, $total - self::bookedUnits($roomTypeId, $in, $out));
    }

    public static function nights(string $in, string $out): int
    {
        $a = new \DateTime($in); $b = new \DateTime($out);
        return max(1, (int) $a->diff($b)->days);
    }

    /** Compute price quote for a stay. */
    public static function quote(array $roomType, string $in, string $out, int $rooms = 1, ?array $offer = null): array
    {
        $nights = self::nights($in, $out);
        $subtotal = $nights * (float) $roomType['base_price'] * $rooms;
        $discount = 0;
        if ($offer) {
            if ($offer['discount_type'] === 'percent') {
                $discount = round($subtotal * ((float)$offer['discount_value'] / 100), 2);
            } else {
                $discount = min($subtotal, (float)$offer['discount_value']);
            }
        }
        return [
            'nights'   => $nights,
            'rooms'    => $rooms,
            'rate'     => (float) $roomType['base_price'],
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total'    => max(0, $subtotal - $discount),
        ];
    }

    public static function generateReference(): string
    {
        do {
            $ref = 'RGE-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            $exists = self::db()->scalar('SELECT COUNT(*) FROM bookings WHERE reference = ?', [$ref]);
        } while ($exists);
        return $ref;
    }

    public static function create(array $data): array
    {
        $data['reference'] = self::generateReference();
        $id = self::db()->insert('bookings', $data);
        return self::find($id);
    }

    public static function find(int $id): ?array
    {
        return self::db()->first('SELECT * FROM bookings WHERE id = ?', [$id]);
    }

    public static function findByReference(string $ref): ?array
    {
        return self::db()->first('SELECT * FROM bookings WHERE reference = ?', [$ref]);
    }

    public static function markPaid(int $bookingId): void
    {
        self::db()->update('bookings',
            ['payment_status' => 'paid', 'status' => 'confirmed', 'updated_at' => date('c')],
            ['id' => $bookingId]);
    }
}
