<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Folio;
use App\Models\Booking;
use App\Models\Content;

class BookingController extends Controller
{
    /** Walk-in / phone booking form for front desk. */
    public function createForm(): string
    {
        $rooms = $this->db->all('SELECT id,name,base_price,weekend_price,max_occupancy,adults,total_units FROM room_types WHERE is_published=1 ORDER BY sort_order,id');
        return $this->view('admin.booking-new', [
            'active' => 'bookings', 'pageTitle' => 'New Walk-in Booking',
            'rooms' => $rooms,
            'check_in' => date('Y-m-d'),
            'check_out' => date('Y-m-d', strtotime('+1 day')),
        ], 'admin');
    }

    /** Persist a manual booking created by staff. */
    public function store(): string
    {
        $this->requirePost();
        $room  = $this->db->first('SELECT * FROM room_types WHERE id=?', [$this->input('room_type_id')]);
        $in    = $this->input('check_in');
        $out   = $this->input('check_out');
        $rooms = max(1, (int) $this->input('rooms_count', 1));
        $name  = trim((string) $this->input('guest_name'));

        $errors = [];
        if (!$room) $errors[] = 'Please choose a room type.';
        if (!$in || !$out || strtotime($out) <= strtotime($in)) $errors[] = 'Please enter valid check-in and check-out dates.';
        if ($name === '') $errors[] = 'Guest name is required.';
        if ($room && $in && $out) {
            $avail = Booking::availableUnits((int) $room['id'], $in, $out);
            if ($rooms > $avail) $errors[] = "Only $avail unit(s) of that room are available for those dates.";
        }
        if ($errors) { flash('error', implode(' ', $errors)); redirect('/admin/bookings/new'); return ''; }

        $offerCode = trim((string) $this->input('offer_code'));
        $offer = $offerCode ? Content::offerByCode($offerCode) : null;
        $quote = Booking::quote($room, $in, $out, $rooms, $offer);

        $status = in_array($this->input('status'), Booking::ACTIVE_STATUSES, true) ? $this->input('status') : 'confirmed';
        $email  = filter_var($this->input('guest_email'), FILTER_VALIDATE_EMAIL) ?: '';

        $booking = Booking::create([
            'room_type_id' => $room['id'], 'check_in' => $in, 'check_out' => $out,
            'nights' => $quote['nights'], 'rooms_count' => $rooms,
            'adults' => (int) $this->input('adults', $room['adults']), 'children' => (int) $this->input('children', 0),
            'guest_name' => $name, 'guest_email' => $email,
            'guest_phone' => trim((string) $this->input('guest_phone')) ?: null,
            'guest_country' => trim((string) $this->input('guest_country')) ?: null,
            'special_requests' => trim((string) $this->input('special_requests')) ?: null,
            'offer_code' => $offer['code'] ?? null,
            'subtotal' => $quote['subtotal'], 'discount' => $quote['discount'], 'total' => $quote['total'],
            'currency' => 'PHP', 'status' => $status, 'payment_status' => 'unpaid', 'source' => 'walk_in',
        ]);

        // Optional on-the-spot payment (cash / card / bank at the desk).
        if ($this->input('mark_paid')) {
            $method = in_array($this->input('payment_method'), ['cash','card','bank','gcash'], true) ? $this->input('payment_method') : 'cash';
            $amount = (float) $this->input('amount_paid') ?: (float) $quote['total'];
            Folio::recordCashSettlement((int) $booking['id'], $amount, $method, Auth::id());
        }

        flash('success', 'Walk-in booking ' . $booking['reference'] . ' created.');
        redirect('/admin/bookings/' . $booking['id']);
        return '';
    }
    public function index(): string
    {
        $status = $this->input('status');
        $sql = "SELECT b.*, rt.name AS room_name,
                       COALESCE((SELECT SUM(amount) FROM room_charges c WHERE c.booking_id=b.id AND c.status!='void'),0) AS charges_total
                FROM bookings b JOIN room_types rt ON rt.id=b.room_type_id";
        $params = [];
        if ($status) { $sql .= ' WHERE b.status = ?'; $params[] = $status; }
        $sql .= ' ORDER BY b.created_at DESC LIMIT 200';
        return $this->view('admin.bookings-index', [
            'active' => 'bookings', 'pageTitle' => 'Bookings',
            'bookings' => $this->db->all($sql, $params), 'status' => $status,
        ], 'admin');
    }

    public function show(string $id): string
    {
        $b = $this->db->first('SELECT b.*, rt.name AS room_name, rt.slug AS room_slug FROM bookings b JOIN room_types rt ON rt.id=b.room_type_id WHERE b.id=?', [$id]);
        if (!$b) return $this->abort(404, 'Booking not found');
        $payments = $this->db->all('SELECT * FROM payments WHERE booking_id=? ORDER BY id DESC', [$id]);
        return $this->view('admin.booking-show', [
            'active' => 'bookings', 'pageTitle' => 'Booking ' . $b['reference'],
            'b' => $b, 'payments' => $payments,
            'charges' => Folio::charges((int) $b['id']),
            'folio' => Folio::summary($b),
            'categories' => Folio::CATEGORIES,
        ], 'admin');
    }

    public function update(string $id): string
    {
        $this->requirePost();
        $b = $this->db->first('SELECT * FROM bookings WHERE id=?', [$id]);
        if (!$b) return $this->abort(404);
        $this->db->update('bookings', [
            'status' => $this->input('status', $b['status']),
            'payment_status' => $this->input('payment_status', $b['payment_status']),
            'special_requests' => $this->input('special_requests', $b['special_requests']),
            'updated_at' => date('c'),
        ], ['id' => $id]);
        flash('success', 'Booking updated.');
        redirect('/admin/bookings/' . $id);
        return '';
    }

    /** Permanently delete a booking and its records. Super admin only. */
    public function destroy(string $id): string
    {
        $this->requirePost();
        if (!Auth::isAdmin()) { http_response_code(403); echo $this->view('errors.403', [], 'admin'); exit; }
        $b = $this->db->first('SELECT * FROM bookings WHERE id=?', [$id]);
        if (!$b) return $this->abort(404, 'Booking not found');
        // refunds has no ON DELETE CASCADE; remove first. payments/room_charges/booking_rooms cascade.
        $this->db->delete('refunds', ['booking_id' => $id]);
        $this->db->delete('bookings', ['id' => $id]);
        flash('success', 'Booking ' . $b['reference'] . ' and its related records were permanently deleted.');
        redirect('/admin/bookings');
        return '';
    }
}
