<?php
namespace App\Models;

use App\Core\Database;

/**
 * Guest folio: the room booking plus any in-house incidental charges
 * (room service, minibar, laundry, spa, amenity usage, transfers, …).
 *
 * The booking's `total` is the room portion; charges are tracked in
 * room_charges. The grand total is room + charges, and the outstanding
 * balance is grand total − payments already settled.
 *
 * reconcile() is the single source of truth: call it after any payment
 * event (online webhook/return, manual cash entry) or after charges change,
 * and it marks charges paid and updates the booking's payment_status.
 */
class Folio
{
    private static function db(): Database { return Database::instance(); }

    /** Human labels for charge categories (keys are stored in room_charges.category). */
    public const CATEGORIES = [
        'room_service'   => 'Room service',
        'food_beverage'  => 'Food & beverage',
        'minibar'        => 'Minibar',
        'laundry'        => 'Laundry',
        'spa'            => 'Spa & wellness',
        'amenity'        => 'Amenity usage',
        'transfer'       => 'Transfer / transport',
        'other'          => 'Other',
    ];

    public static function categoryLabel(string $key): string
    {
        return self::CATEGORIES[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    /** Non-void charges for a booking, newest first. */
    public static function charges(int $bookingId): array
    {
        return self::db()->all(
            "SELECT c.*, u.name AS recorder FROM room_charges c
             LEFT JOIN users u ON u.id = c.recorded_by
             WHERE c.booking_id = ? AND c.status != 'void'
             ORDER BY c.charged_at DESC, c.id DESC",
            [$bookingId]
        );
    }

    /** Sum of all non-void charges. */
    public static function chargesTotal(int $bookingId): float
    {
        return (float) self::db()->scalar(
            "SELECT COALESCE(SUM(amount),0) FROM room_charges WHERE booking_id = ? AND status != 'void'",
            [$bookingId]
        );
    }

    /** Total settled (paid) across all payment records for the booking. */
    public static function paid(int $bookingId): float
    {
        return (float) self::db()->scalar(
            "SELECT COALESCE(SUM(amount),0) FROM payments WHERE booking_id = ? AND status = 'paid'",
            [$bookingId]
        );
    }

    /**
     * Folio summary for a booking row.
     * @return array{room_total:float,charges_total:float,grand_total:float,paid:float,balance:float}
     */
    public static function summary(array $booking): array
    {
        $room    = (float) $booking['total'];
        $charges = self::chargesTotal((int) $booking['id']);
        $grand   = $room + $charges;
        $paid    = self::paid((int) $booking['id']);
        return [
            'room_total'    => $room,
            'charges_total' => $charges,
            'grand_total'   => $grand,
            'paid'          => $paid,
            'balance'       => max(0, round($grand - $paid, 2)),
        ];
    }

    /** Post a new charge to a booking. Returns the new charge id. */
    public static function addCharge(int $bookingId, array $data): int
    {
        $qty   = max(0, (float) ($data['quantity'] ?? 1));
        $unit  = (float) ($data['unit_price'] ?? 0);
        $id = self::db()->insert('room_charges', [
            'booking_id'  => $bookingId,
            'category'    => $data['category'] ?? 'other',
            'description' => trim((string) ($data['description'] ?? '')),
            'quantity'    => $qty,
            'unit_price'  => $unit,
            'amount'      => round($qty * $unit, 2),
            'status'      => 'unpaid',
            'charged_at'  => $data['charged_at'] ?? date('Y-m-d'),
            'notes'       => $data['notes'] ?? null,
            'recorded_by' => $data['recorded_by'] ?? null,
        ]);
        self::reconcile($bookingId);
        return $id;
    }

    /** Void a charge (kept for audit, excluded from totals). */
    public static function voidCharge(int $bookingId, int $chargeId): void
    {
        self::db()->update('room_charges',
            ['status' => 'void', 'updated_at' => date('c')],
            ['id' => $chargeId, 'booking_id' => $bookingId]);
        self::reconcile($bookingId);
    }

    /**
     * Recompute the booking's payment state from the folio.
     * - paid covers grand total  → charges marked paid, booking 'paid'
     * - some paid, balance left   → 'partial'
     * - nothing paid              → 'unpaid'
     * Refunded bookings are left untouched.
     */
    public static function reconcile(int $bookingId): void
    {
        $b = self::db()->first('SELECT * FROM bookings WHERE id = ?', [$bookingId]);
        if (!$b || $b['payment_status'] === 'refunded') return;

        $grand = (float) $b['total'] + self::chargesTotal($bookingId);
        $paid  = self::paid($bookingId);

        if ($paid > 0 && $paid + 0.01 >= $grand) {
            self::db()->run(
                "UPDATE room_charges SET status='paid', updated_at=? WHERE booking_id=? AND status='unpaid'",
                [date('c'), $bookingId]
            );
            self::db()->update('bookings', [
                'payment_status' => 'paid',
                'status'         => $b['status'] === 'pending' ? 'confirmed' : $b['status'],
                'updated_at'     => date('c'),
            ], ['id' => $bookingId]);
        } else {
            self::db()->update('bookings', [
                'payment_status' => $paid > 0 ? 'partial' : 'unpaid',
                'updated_at'     => date('c'),
            ], ['id' => $bookingId]);
        }
    }

    /** Record a manual cash/on-site settlement against a booking, then reconcile. */
    public static function recordCashSettlement(int $bookingId, float $amount, string $method = 'cash', ?int $userId = null): void
    {
        $b = self::db()->first('SELECT currency FROM bookings WHERE id = ?', [$bookingId]);
        self::db()->insert('payments', [
            'booking_id'  => $bookingId,
            'provider'    => 'cash',
            'method'      => $method,
            'amount'      => round($amount, 2),
            'currency'    => $b['currency'] ?? 'PHP',
            'status'      => 'paid',
            'external_id' => 'CASH-' . strtoupper(bin2hex(random_bytes(3))),
            'payload'     => json_encode(['recorded_by' => $userId, 'note' => 'Folio cash settlement']),
        ]);
        self::reconcile($bookingId);
    }

    /**
     * In-house guests (active bookings) with their folio totals — drives the
     * admin Billing page for checkouts and settlements. Newest checkout first.
     */
    public static function billingList(bool $onlyOutstanding = false): array
    {
        $rows = self::db()->all(
            "SELECT b.id, b.reference, b.guest_name, b.check_in, b.check_out, b.status, b.payment_status,
                    b.total AS room_total, b.currency, rt.name AS room_name,
                    COALESCE((SELECT SUM(amount) FROM room_charges c WHERE c.booking_id=b.id AND c.status!='void'),0) AS charges_total,
                    COALESCE((SELECT SUM(amount) FROM payments p WHERE p.booking_id=b.id AND p.status='paid'),0) AS paid
             FROM bookings b JOIN room_types rt ON rt.id = b.room_type_id
             WHERE b.status IN ('pending','confirmed','checked_in')
             ORDER BY date(b.check_out) ASC, b.id ASC"
        );
        foreach ($rows as &$r) {
            $r['grand_total'] = (float) $r['room_total'] + (float) $r['charges_total'];
            $r['balance']     = max(0, round($r['grand_total'] - (float) $r['paid'], 2));
        }
        unset($r);
        if ($onlyOutstanding) {
            $rows = array_values(array_filter($rows, fn($r) => $r['balance'] > 0.005));
        }
        return $rows;
    }

    /** Headline figures for the Billing page. */
    public static function billingSummary(array $rows): array
    {
        $outstanding = 0.0; $withBalance = 0; $dueToday = 0;
        $today = date('Y-m-d');
        foreach ($rows as $r) {
            if ($r['balance'] > 0.005) { $outstanding += $r['balance']; $withBalance++; }
            if (date('Y-m-d', strtotime($r['check_out'])) === $today) $dueToday++;
        }
        return [
            'count'               => count($rows),
            'guests_with_balance' => $withBalance,
            'outstanding'         => $outstanding,
            'due_today'           => $dueToday,
        ];
    }
}
