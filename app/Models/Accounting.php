<?php
namespace App\Models;

use App\Core\Database;

/**
 * Read/aggregate helpers for the accounting module.
 * "Income" = paid booking payments + other income. Net = income − refunds − expenses.
 */
class Accounting
{
    private static function db(): Database { return Database::instance(); }

    /** Default date range: current month. */
    public static function range(?string $from, ?string $to): array
    {
        $from = $from ?: date('Y-m-01');
        $to   = $to ?: date('Y-m-d');
        return [$from, $to];
    }

    public static function bookingRevenue(string $from, string $to): float
    {
        return (float) self::db()->scalar(
            "SELECT COALESCE(SUM(amount),0) FROM payments
             WHERE status='paid' AND date(created_at) BETWEEN date(?) AND date(?)",
            [$from, $to]
        );
    }

    public static function otherIncome(string $from, string $to): float
    {
        return (float) self::db()->scalar(
            "SELECT COALESCE(SUM(amount),0) FROM other_income WHERE date(income_date) BETWEEN date(?) AND date(?)",
            [$from, $to]
        );
    }

    public static function expenses(string $from, string $to): float
    {
        return (float) self::db()->scalar(
            "SELECT COALESCE(SUM(amount),0) FROM expenses WHERE date(expense_date) BETWEEN date(?) AND date(?)",
            [$from, $to]
        );
    }

    public static function refunds(string $from, string $to): float
    {
        return (float) self::db()->scalar(
            "SELECT COALESCE(SUM(amount),0) FROM refunds WHERE date(created_at) BETWEEN date(?) AND date(?)",
            [$from, $to]
        );
    }

    /** Outstanding = confirmed/pending bookings not yet paid. */
    public static function outstanding(): float
    {
        return (float) self::db()->scalar(
            "SELECT COALESCE(SUM(total),0) FROM bookings
             WHERE payment_status IN ('unpaid','partial') AND status IN ('pending','confirmed','checked_in')"
        );
    }

    public static function summary(string $from, string $to): array
    {
        $booking = self::bookingRevenue($from, $to);
        $other   = self::otherIncome($from, $to);
        $exp     = self::expenses($from, $to);
        $ref     = self::refunds($from, $to);
        $income  = $booking + $other;
        $net     = $income - $ref - $exp;
        return [
            'booking_revenue' => $booking,
            'other_income'    => $other,
            'income'          => $income,
            'expenses'        => $exp,
            'refunds'         => $ref,
            'net'             => $net,
            'outstanding'     => self::outstanding(),
            'vat'             => vat_breakdown($income),
        ];
    }

    /* ---------------- Ledgers ---------------- */
    public static function paymentsLedger(string $from, string $to, ?string $status = null, ?string $provider = null): array
    {
        $sql = "SELECT p.*, b.reference, b.guest_name FROM payments p
                LEFT JOIN bookings b ON b.id = p.booking_id
                WHERE date(p.created_at) BETWEEN date(?) AND date(?)";
        $params = [$from, $to];
        if ($status)   { $sql .= ' AND p.status = ?';   $params[] = $status; }
        if ($provider) { $sql .= ' AND p.provider = ?'; $params[] = $provider; }
        $sql .= ' ORDER BY p.created_at DESC';
        return self::db()->all($sql, $params);
    }

    public static function expensesList(string $from, string $to, ?int $categoryId = null): array
    {
        $sql = "SELECT e.*, c.name AS category_name, u.name AS recorder
                FROM expenses e
                LEFT JOIN expense_categories c ON c.id = e.category_id
                LEFT JOIN users u ON u.id = e.recorded_by
                WHERE date(e.expense_date) BETWEEN date(?) AND date(?)";
        $params = [$from, $to];
        if ($categoryId) { $sql .= ' AND e.category_id = ?'; $params[] = $categoryId; }
        $sql .= ' ORDER BY e.expense_date DESC, e.id DESC';
        return self::db()->all($sql, $params);
    }

    public static function refundsList(string $from, string $to): array
    {
        return self::db()->all(
            "SELECT r.*, b.reference, b.guest_name, u.name AS refunder
             FROM refunds r
             LEFT JOIN bookings b ON b.id = r.booking_id
             LEFT JOIN users u ON u.id = r.refunded_by
             WHERE date(r.created_at) BETWEEN date(?) AND date(?)
             ORDER BY r.created_at DESC",
            [$from, $to]
        );
    }

    /* ---------------- Breakdowns ---------------- */
    public static function revenueByRoomType(string $from, string $to): array
    {
        return self::db()->all(
            "SELECT rt.name, COALESCE(SUM(p.amount),0) total
             FROM payments p JOIN bookings b ON b.id=p.booking_id JOIN room_types rt ON rt.id=b.room_type_id
             WHERE p.status='paid' AND date(p.created_at) BETWEEN date(?) AND date(?)
             GROUP BY rt.id ORDER BY total DESC",
            [$from, $to]
        );
    }

    public static function revenueByProvider(string $from, string $to): array
    {
        return self::db()->all(
            "SELECT provider, COALESCE(SUM(amount),0) total, COUNT(*) cnt
             FROM payments WHERE status='paid' AND date(created_at) BETWEEN date(?) AND date(?)
             GROUP BY provider ORDER BY total DESC",
            [$from, $to]
        );
    }

    public static function expensesByCategory(string $from, string $to): array
    {
        return self::db()->all(
            "SELECT COALESCE(c.name,'Uncategorised') name, COALESCE(SUM(e.amount),0) total
             FROM expenses e LEFT JOIN expense_categories c ON c.id=e.category_id
             WHERE date(e.expense_date) BETWEEN date(?) AND date(?)
             GROUP BY e.category_id ORDER BY total DESC",
            [$from, $to]
        );
    }

    public static function dailyRevenue(string $from, string $to): array
    {
        return self::db()->all(
            "SELECT date(created_at) d, COALESCE(SUM(amount),0) total
             FROM payments WHERE status='paid' AND date(created_at) BETWEEN date(?) AND date(?)
             GROUP BY date(created_at) ORDER BY d",
            [$from, $to]
        );
    }

    public static function categories(): array
    {
        return self::db()->all('SELECT * FROM expense_categories ORDER BY name');
    }
}
