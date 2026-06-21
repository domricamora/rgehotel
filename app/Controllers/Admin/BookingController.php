<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Folio;

class BookingController extends Controller
{
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
