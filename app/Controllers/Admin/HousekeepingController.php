<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class HousekeepingController extends Controller
{
    public function index(): string
    {
        $rooms = $this->db->all(
            'SELECT r.*, rt.name AS room_name FROM rooms r JOIN room_types rt ON rt.id=r.room_type_id ORDER BY rt.name, r.code'
        );
        return $this->view('admin.housekeeping', ['active'=>'housekeeping','pageTitle'=>'Housekeeping','rooms'=>$rooms,
            'canManage'=>\App\Core\Auth::can('housekeeping.manage')], 'admin');
    }

    public function update(string $id): string
    {
        $this->requirePost();
        $this->db->update('rooms', [
            'status' => $this->input('status'),
            'housekeeping' => $this->input('housekeeping'),
            'notes' => $this->input('notes'),
        ], ['id' => $id]);
        flash('success', 'Room status updated.');
        redirect('/admin/housekeeping');
        return '';
    }
}
