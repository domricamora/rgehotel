<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class RoomController extends Controller
{
    public function index(): string
    {
        $rooms = $this->db->all(
            'SELECT rt.*, (SELECT filename FROM room_photos WHERE room_type_id=rt.id AND is_cover=1 LIMIT 1) cover,
             (SELECT COUNT(*) FROM rooms WHERE room_type_id=rt.id) units
             FROM room_types rt ORDER BY rt.sort_order, rt.id'
        );
        return $this->view('admin.rooms-index', ['active'=>'rooms','pageTitle'=>'Room Types','rooms'=>$rooms], 'admin');
    }

    public function edit(string $id): string
    {
        $room = $this->db->first('SELECT * FROM room_types WHERE id=?', [$id]);
        if (!$room) return $this->abort(404);
        return $this->view('admin.room-edit', [
            'active'=>'rooms','pageTitle'=>'Edit · '.$room['name'],'room'=>$room,
            'photos'=>$this->db->all('SELECT * FROM room_photos WHERE room_type_id=? ORDER BY is_cover DESC, sort_order',[$id]),
        ], 'admin');
    }

    public function update(string $id): string
    {
        $this->requirePost();
        $room = $this->db->first('SELECT * FROM room_types WHERE id=?', [$id]);
        if (!$room) return $this->abort(404);
        $this->db->update('room_types', [
            'name'          => $this->input('name', $room['name']),
            'summary'       => $this->input('summary'),
            'description'   => $this->input('description'),
            'base_price'    => (float)$this->input('base_price'),
            'weekend_price' => (float)$this->input('weekend_price') ?: null,
            'max_occupancy' => (int)$this->input('max_occupancy'),
            'beds'          => $this->input('beds'),
            'size_sqm'      => (int)$this->input('size_sqm') ?: null,
            'view'          => $this->input('view'),
            'total_units'   => (int)$this->input('total_units'),
            'is_published'  => $this->input('is_published') ? 1 : 0,
            'is_featured'   => $this->input('is_featured') ? 1 : 0,
            'meta_title'    => $this->input('meta_title'),
            'meta_description' => $this->input('meta_description'),
            'updated_at'    => date('c'),
        ], ['id' => $id]);
        flash('success', 'Room type updated.');
        redirect('/admin/rooms/' . $id);
        return '';
    }
}
