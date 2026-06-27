<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\ImageService;

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
        $isNew = $id === 'new';
        $room = $isNew ? [] : $this->db->first('SELECT * FROM room_types WHERE id=?', [$id]);
        if (!$isNew && !$room) return $this->abort(404);
        return $this->view('admin.room-edit', [
            'active'  => 'rooms',
            'pageTitle' => $isNew ? 'New Room Type' : 'Edit · '.$room['name'],
            'room'    => $room ?: [],
            'id'      => $id,
            'photos'  => $isNew ? [] : $this->db->all('SELECT * FROM room_photos WHERE room_type_id=? ORDER BY is_cover DESC, sort_order',[$id]),
        ], 'admin');
    }

    public function update(string $id): string
    {
        $this->requirePost();
        $isNew = $id === 'new';

        $data = [
            'name'          => trim((string)$this->input('name')),
            'summary'       => $this->input('summary'),
            'description'   => $this->input('description'),
            'base_price'    => (float)$this->input('base_price'),
            'weekend_price' => (float)$this->input('weekend_price') ?: null,
            'max_occupancy' => (int)$this->input('max_occupancy') ?: 1,
            'adults'        => (int)$this->input('adults') ?: 1,
            'children'      => (int)$this->input('children'),
            'beds'          => $this->input('beds'),
            'size_sqm'      => (int)$this->input('size_sqm') ?: null,
            'view'          => $this->input('view'),
            'total_units'   => (int)$this->input('total_units') ?: 1,
            'sort_order'    => (int)$this->input('sort_order'),
            'is_published'  => $this->input('is_published') ? 1 : 0,
            'is_featured'   => $this->input('is_featured') ? 1 : 0,
            'meta_title'    => $this->input('meta_title'),
            'meta_description' => $this->input('meta_description'),
            'updated_at'    => date('c'),
        ];
        if ($data['name'] === '') { flash('error','Name is required.'); redirect('/admin/rooms/'.$id); return ''; }

        if ($isNew) {
            $data['slug'] = $this->uniqueSlug($this->input('slug') ?: slugify($data['name']));
            $newId = $this->db->insert('room_types', $data);
            flash('success', 'Room type created. You can now upload photos below.');
            redirect('/admin/rooms/'.$newId);
            return '';
        }

        $room = $this->db->first('SELECT * FROM room_types WHERE id=?', [$id]);
        if (!$room) return $this->abort(404);
        $this->db->update('room_types', $data, ['id' => $id]);
        flash('success', 'Room type updated.');
        redirect('/admin/rooms/' . $id);
        return '';
    }

    public function destroy(string $id): string
    {
        $this->requirePost();
        $room = $this->db->first('SELECT * FROM room_types WHERE id=?', [$id]);
        if (!$room) return $this->abort(404);

        // Bookings reference room_types without cascade — refuse if any exist.
        $bookings = (int)$this->db->scalar('SELECT COUNT(*) FROM bookings WHERE room_type_id=?', [$id]);
        if ($bookings > 0) {
            flash('error', "Cannot delete \"{$room['name']}\" — it has $bookings booking(s). Unpublish it instead.");
            redirect('/admin/rooms/'.$id);
            return '';
        }

        // Remove uploaded photo files (DB rows cascade on delete).
        foreach ($this->db->all('SELECT filename FROM room_photos WHERE room_type_id=?', [$id]) as $p) {
            ImageService::deleteBase($p['filename']);
        }
        $this->db->delete('room_types', ['id' => $id]);
        flash('success', "Room type \"{$room['name']}\" deleted.");
        redirect('/admin/rooms');
        return '';
    }

    public function uploadPhoto(string $id): string
    {
        $this->requirePost();
        $room = $this->db->first('SELECT * FROM room_types WHERE id=?', [$id]);
        if (!$room) return $this->abort(404);

        if (!ImageService::available()) {
            flash('error', 'Image processing (GD/WebP) is unavailable on this server.');
            redirect('/admin/rooms/'.$id);
            return '';
        }

        $files = $this->normalizeFiles($_FILES['photos'] ?? null);
        if (!$files) { flash('error','No image selected.'); redirect('/admin/rooms/'.$id); return ''; }

        $hasCover = (int)$this->db->scalar('SELECT COUNT(*) FROM room_photos WHERE room_type_id=? AND is_cover=1', [$id]) > 0;
        $sort = (int)$this->db->scalar('SELECT COALESCE(MAX(sort_order),-1)+1 FROM room_photos WHERE room_type_id=?', [$id]);
        $added = 0;
        foreach ($files as $file) {
            $base = ImageService::ingestUpload($file, ImageService::uniqueBase('rooms/'.$room['slug']));
            if (!$base) continue;
            $this->db->insert('room_photos', [
                'room_type_id' => $id,
                'filename'     => $base,
                'alt'          => $room['name'].' at RGE Hotel',
                'is_cover'     => (!$hasCover && $added === 0) ? 1 : 0,
                'sort_order'   => $sort++,
            ]);
            $added++;
        }
        flash($added ? 'success' : 'error', $added ? "$added photo(s) uploaded." : 'Upload failed — unsupported or invalid image.');
        redirect('/admin/rooms/'.$id);
        return '';
    }

    public function deletePhoto(string $id, string $pid): string
    {
        $this->requirePost();
        $photo = $this->db->first('SELECT * FROM room_photos WHERE id=? AND room_type_id=?', [$pid, $id]);
        if (!$photo) return $this->abort(404);
        ImageService::deleteBase($photo['filename']);
        $this->db->delete('room_photos', ['id' => $pid]);
        // If we removed the cover, promote the next photo.
        if ($photo['is_cover']) {
            $next = $this->db->first('SELECT id FROM room_photos WHERE room_type_id=? ORDER BY sort_order LIMIT 1', [$id]);
            if ($next) $this->db->update('room_photos', ['is_cover'=>1], ['id'=>$next['id']]);
        }
        flash('success', 'Photo deleted.');
        redirect('/admin/rooms/'.$id);
        return '';
    }

    public function setCover(string $id, string $pid): string
    {
        $this->requirePost();
        $photo = $this->db->first('SELECT * FROM room_photos WHERE id=? AND room_type_id=?', [$pid, $id]);
        if (!$photo) return $this->abort(404);
        $this->db->run('UPDATE room_photos SET is_cover=0 WHERE room_type_id=?', [$id]);
        $this->db->update('room_photos', ['is_cover'=>1], ['id'=>$pid]);
        flash('success', 'Cover photo updated.');
        redirect('/admin/rooms/'.$id);
        return '';
    }

    /* ----------------------------------------------------------- helpers */

    private function uniqueSlug(string $slug): string
    {
        $slug = $slug ?: 'room';
        $base = $slug; $n = 2;
        while ($this->db->scalar('SELECT COUNT(*) FROM room_types WHERE slug=?', [$slug]) > 0) {
            $slug = $base.'-'.$n++;
        }
        return $slug;
    }

    /** Normalize $_FILES['photos'] (multiple) into a list of single-file arrays. */
    private function normalizeFiles($f): array
    {
        if (!$f || !isset($f['name'])) return [];
        $out = [];
        if (is_array($f['name'])) {
            foreach ($f['name'] as $i => $name) {
                if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
                $out[] = ['name'=>$name,'type'=>$f['type'][$i]??'','tmp_name'=>$f['tmp_name'][$i],'error'=>$f['error'][$i],'size'=>$f['size'][$i]??0];
            }
        } elseif (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $out[] = $f;
        }
        return $out;
    }
}
