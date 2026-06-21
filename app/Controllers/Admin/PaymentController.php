<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class PaymentController extends Controller
{
    public function index(): string
    {
        $payments = $this->db->all(
            'SELECT p.*, b.reference, b.guest_name FROM payments p
             JOIN bookings b ON b.id = p.booking_id ORDER BY p.created_at DESC LIMIT 200'
        );
        $summary = [
            'paid'  => (float)$this->db->scalar("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid'"),
            'count' => (int)$this->db->scalar("SELECT COUNT(*) FROM payments WHERE status='paid'"),
            'pending' => (int)$this->db->scalar("SELECT COUNT(*) FROM payments WHERE status='pending'"),
        ];
        return $this->view('admin.payments', ['active'=>'payments','pageTitle'=>'Payments','payments'=>$payments,'summary'=>$summary], 'admin');
    }
}
