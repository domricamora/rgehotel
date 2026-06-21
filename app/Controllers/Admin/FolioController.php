<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Folio;
use App\Models\Booking;

/**
 * Front-desk management of a booking's in-house folio:
 * post charges (room service, amenities, etc.), void them, and
 * settle the outstanding balance as a manual cash entry.
 */
class FolioController extends Controller
{
    /** POST /admin/bookings/{id}/charges — post a new charge. */
    public function addCharge(string $id): string
    {
        $this->requirePost();
        $booking = Booking::find((int) $id);
        if (!$booking) return $this->abort(404, 'Booking not found');

        $description = trim((string) $this->input('description'));
        $quantity    = (float) $this->input('quantity', 1);
        $unitPrice   = (float) $this->input('unit_price');
        $category    = (string) $this->input('category', 'other');
        if (!array_key_exists($category, Folio::CATEGORIES)) $category = 'other';

        if ($description === '' || $quantity <= 0 || $unitPrice <= 0) {
            flash('error', 'Enter a description, quantity and unit price.');
            redirect('/admin/bookings/' . $id);
            return '';
        }

        Folio::addCharge((int) $id, [
            'category'    => $category,
            'description' => $description,
            'quantity'    => $quantity,
            'unit_price'  => $unitPrice,
            'charged_at'  => $this->input('charged_at') ?: date('Y-m-d'),
            'notes'       => trim((string) $this->input('notes')) ?: null,
            'recorded_by' => Auth::id(),
        ]);
        flash('success', 'Charge added to the folio.');
        redirect('/admin/bookings/' . $id);
        return '';
    }

    /** POST /admin/bookings/{id}/charges/{cid}/void */
    public function voidCharge(string $id, string $cid): string
    {
        $this->requirePost();
        Folio::voidCharge((int) $id, (int) $cid);
        flash('success', 'Charge voided.');
        redirect('/admin/bookings/' . $id);
        return '';
    }

    /** POST /admin/bookings/{id}/settle — record a cash settlement of the balance. */
    public function settleCash(string $id): string
    {
        $this->requirePost();
        $booking = Booking::find((int) $id);
        if (!$booking) return $this->abort(404, 'Booking not found');

        $summary = Folio::summary($booking);
        $amount  = (float) ($this->input('amount') ?: $summary['balance']);
        if ($amount <= 0) {
            flash('error', 'There is no outstanding balance to settle.');
            redirect('/admin/bookings/' . $id);
            return '';
        }

        $this->db->insert('payments', [
            'booking_id'  => $booking['id'],
            'provider'    => 'cash',
            'method'      => $this->input('method', 'cash'),
            'amount'      => round($amount, 2),
            'currency'    => $booking['currency'] ?: 'PHP',
            'status'      => 'paid',
            'external_id' => 'CASH-' . strtoupper(bin2hex(random_bytes(3))),
            'payload'     => json_encode(['recorded_by' => Auth::id(), 'note' => 'Folio cash settlement']),
        ]);
        Folio::reconcile((int) $booking['id']);
        flash('success', 'Cash payment of ' . money($amount) . ' recorded.');
        redirect('/admin/bookings/' . $id);
        return '';
    }
}
