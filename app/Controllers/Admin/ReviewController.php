<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class ReviewController extends Controller
{
    public function index(): string
    {
        $reviews = $this->db->all(
            "SELECT r.*, rt.name AS room_name FROM reviews r
             LEFT JOIN room_types rt ON rt.id = r.subject_id AND r.subject_type='room_type'
             ORDER BY r.is_approved ASC, r.created_at DESC LIMIT 200"
        );
        return $this->view('admin.reviews-index', ['active'=>'reviews','pageTitle'=>'Reviews','reviews'=>$reviews], 'admin');
    }

    public function update(string $id): string
    {
        $this->requirePost();
        $action = $this->input('action');
        if ($action === 'delete') {
            $this->db->delete('reviews', ['id' => $id]);
            flash('success', 'Review deleted.');
        } else {
            $this->db->update('reviews', ['is_approved' => $action === 'approve' ? 1 : 0], ['id' => $id]);
            flash('success', 'Review ' . ($action === 'approve' ? 'approved' : 'unpublished') . '.');
        }
        redirect('/admin/reviews');
        return '';
    }
}
