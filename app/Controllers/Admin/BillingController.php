<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Folio;
use App\Models\Booking;

/**
 * Front-desk billing overview: in-house guests with their folio balances,
 * for fast checkouts and settlements.
 */
class BillingController extends Controller
{
    private function requireManage(): void
    {
        if (!Auth::can('bookings.manage')) { http_response_code(403); echo $this->view('errors.403', [], 'admin'); exit; }
    }

    /** GET /admin/billing */
    public function index(): string
    {
        $all  = Folio::billingList(false);
        $only = $this->input('filter') === 'outstanding';
        $rows = $only ? array_values(array_filter($all, fn($r) => $r['balance'] > 0.005)) : $all;
        return $this->view('admin.billing-index', [
            'active'    => 'billing',
            'pageTitle' => 'Billing',
            'rows'      => $rows,
            'summary'   => Folio::billingSummary($all),
            'filter'    => $only ? 'outstanding' : 'all',
            'canManage' => Auth::can('bookings.manage'),
        ], 'admin');
    }

    /** POST /admin/billing/{id}/settle — record a cash settlement and stay on the billing list. */
    public function settle(string $id): string
    {
        $this->requirePost(); $this->requireManage();
        $booking = Booking::find((int) $id);
        if (!$booking) return $this->abort(404, 'Booking not found');

        $summary = Folio::summary($booking);
        $amount  = (float) ($this->input('amount') ?: $summary['balance']);
        if ($amount <= 0) {
            flash('error', 'There is no outstanding balance to settle.');
        } else {
            Folio::recordCashSettlement((int) $id, $amount, (string) $this->input('method', 'cash'), Auth::id());
            flash('success', 'Payment of ' . money($amount) . ' recorded for ' . $booking['reference'] . '.');
        }
        redirect('/admin/billing' . ($this->input('filter') === 'outstanding' ? '?filter=outstanding' : ''));
        return '';
    }

    /** POST /admin/billing/{id}/checkout — mark a fully-settled stay as checked out. */
    public function checkout(string $id): string
    {
        $this->requirePost(); $this->requireManage();
        $booking = Booking::find((int) $id);
        if (!$booking) return $this->abort(404, 'Booking not found');

        $summary = Folio::summary($booking);
        if ($summary['balance'] > 0.005) {
            flash('error', 'Settle the ' . money($summary['balance']) . ' balance before checking out ' . $booking['reference'] . '.');
        } else {
            $this->db->update('bookings', ['status' => 'checked_out', 'updated_at' => date('c')], ['id' => $id]);
            flash('success', $booking['guest_name'] . ' checked out (' . $booking['reference'] . ').');
        }
        redirect('/admin/billing' . ($this->input('filter') === 'outstanding' ? '?filter=outstanding' : ''));
        return '';
    }
}
