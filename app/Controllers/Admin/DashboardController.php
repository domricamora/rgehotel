<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class DashboardController extends Controller
{
    public function index(): string
    {
        $db = $this->db;
        $stats = [
            'bookings'     => (int) $db->scalar('SELECT COUNT(*) FROM bookings'),
            'pending'      => (int) $db->scalar("SELECT COUNT(*) FROM bookings WHERE status='pending'"),
            'revenue'      => (float) $db->scalar("SELECT COALESCE(SUM(total),0) FROM bookings WHERE payment_status='paid'"),
            'rooms'        => (int) $db->scalar('SELECT COUNT(*) FROM room_types WHERE is_published=1'),
            'pending_reviews' => (int) $db->scalar('SELECT COUNT(*) FROM reviews WHERE is_approved=0'),
            'messages'     => (int) $db->scalar('SELECT COUNT(*) FROM contact_messages WHERE is_read=0'),
            'occupied'     => (int) $db->scalar("SELECT COUNT(*) FROM rooms WHERE status='occupied'"),
            'units'        => (int) $db->scalar('SELECT COUNT(*) FROM rooms'),
        ];
        $recent = $db->all(
            'SELECT b.*, rt.name AS room_name FROM bookings b
             JOIN room_types rt ON rt.id = b.room_type_id
             ORDER BY b.created_at DESC LIMIT 8'
        );
        $upcoming = $db->all(
            "SELECT b.*, rt.name AS room_name FROM bookings b
             JOIN room_types rt ON rt.id = b.room_type_id
             WHERE date(b.check_in) >= date('now') AND b.status IN ('pending','confirmed')
             ORDER BY b.check_in ASC LIMIT 6"
        );
        return $this->view('admin.dashboard', [
            'active' => 'dashboard', 'pageTitle' => 'Dashboard',
            'stats' => $stats, 'recent' => $recent, 'upcoming' => $upcoming,
        ], 'admin');
    }
}
