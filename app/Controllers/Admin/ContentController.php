<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\ImageService;

/**
 * Generic CRUD for services, packages and offers.
 */
class ContentController extends Controller
{
    private array $map = [
        'services' => ['table' => 'services', 'perm' => 'services.manage', 'title' => 'Services & Tours', 'label' => 'Service'],
        'packages' => ['table' => 'packages', 'perm' => 'packages.manage', 'title' => 'Packages', 'label' => 'Package'],
        'offers'   => ['table' => 'offers',   'perm' => 'offers.manage',   'title' => 'Offers', 'label' => 'Offer'],
    ];

    private function gate(string $entity): array
    {
        if (!isset($this->map[$entity])) { http_response_code(404); exit('Unknown content type'); }
        $cfg = $this->map[$entity];
        if (!Auth::can($cfg['perm'])) { http_response_code(403); echo $this->view('errors.403', [], 'admin'); exit; }
        return $cfg;
    }

    public function index(string $entity): string
    {
        $cfg = $this->gate($entity);
        $rows = $this->db->all("SELECT * FROM {$cfg['table']} ORDER BY sort_order, id");
        return $this->view('admin.content-index', [
            'active' => $entity, 'pageTitle' => $cfg['title'], 'entity' => $entity, 'rows' => $rows, 'label' => $cfg['label'],
        ], 'admin');
    }

    public function edit(string $entity, string $id): string
    {
        $cfg = $this->gate($entity);
        $row = $id === 'new' ? [] : $this->db->first("SELECT * FROM {$cfg['table']} WHERE id=?", [$id]);
        if ($id !== 'new' && !$row) return $this->abort(404);
        return $this->view('admin.content-edit', [
            'active' => $entity, 'pageTitle' => ($id==='new'?'New ':'Edit ').$cfg['label'],
            'entity' => $entity, 'row' => $row ?: [], 'label' => $cfg['label'], 'id' => $id,
        ], 'admin');
    }

    public function save(string $entity, string $id): string
    {
        $cfg = $this->gate($entity);
        $this->requirePost();
        $data = $this->collect($entity);

        // Existing image (for replace/remove handling).
        $current = $id === 'new' ? null : $this->db->first("SELECT image FROM {$cfg['table']} WHERE id=?", [$id]);
        $oldImage = $current['image'] ?? null;

        // New image upload?
        if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $base = ImageService::ingestUpload($_FILES['image'], ImageService::uniqueBase($entity));
            if ($base) {
                $data['image'] = $base;
                ImageService::deleteBase($oldImage); // only removes prior uploads/ files
            } else {
                flash('error', 'Image upload failed — unsupported or invalid file.');
            }
        } elseif ($this->input('remove_image')) {
            $data['image'] = null;
            ImageService::deleteBase($oldImage);
        }

        if ($id === 'new') {
            $data['slug'] = slugify($data['name'] ?? $data['title'] ?? 'item');
            // Ensure slug uniqueness within the table.
            $slug = $data['slug']; $n = 2;
            while ($this->db->scalar("SELECT COUNT(*) FROM {$cfg['table']} WHERE slug=?", [$slug]) > 0) $slug = $data['slug'].'-'.$n++;
            $data['slug'] = $slug;
            $newId = $this->db->insert($cfg['table'], $data);
            flash('success', $cfg['label'] . ' created.');
            redirect("/admin/$entity/$newId");
        } else {
            $this->db->update($cfg['table'], $data, ['id' => $id]);
            flash('success', $cfg['label'] . ' updated.');
            redirect("/admin/$entity/$id");
        }
        return '';
    }

    public function destroy(string $entity, string $id): string
    {
        $cfg = $this->gate($entity);
        $this->requirePost();
        $row = $this->db->first("SELECT * FROM {$cfg['table']} WHERE id=?", [$id]);
        if (!$row) return $this->abort(404);

        // Packages may be referenced by bookings (no cascade) — refuse if so.
        if ($entity === 'packages') {
            $n = (int)$this->db->scalar('SELECT COUNT(*) FROM bookings WHERE package_id=?', [$id]);
            if ($n > 0) {
                flash('error', "Cannot delete this package — it is used by $n booking(s). Unpublish it instead.");
                redirect("/admin/$entity/$id");
                return '';
            }
        }

        ImageService::deleteBase($row['image'] ?? null);
        $this->db->delete($cfg['table'], ['id' => $id]); // link tables cascade
        flash('success', $cfg['label'] . ' deleted.');
        redirect("/admin/$entity");
        return '';
    }

    private function collect(string $entity): array
    {
        $i = fn($k, $d = null) => $this->input($k, $d);
        if ($entity === 'services') {
            return [
                'name' => $i('name'), 'category' => $i('category', 'tour'), 'summary' => $i('summary'),
                'description' => $i('description'), 'price' => (float)$i('price') ?: null, 'price_unit' => $i('price_unit'),
                'duration' => $i('duration'), 'highlights' => $i('highlights'),
                'is_published' => $i('is_published') ? 1 : 0, 'is_featured' => $i('is_featured') ? 1 : 0,
                'sort_order' => (int)$i('sort_order'), 'meta_title' => $i('meta_title'), 'meta_description' => $i('meta_description'),
                'updated_at' => date('c'),
            ];
        }
        if ($entity === 'packages') {
            return [
                'name' => $i('name'), 'summary' => $i('summary'), 'description' => $i('description'),
                'price' => (float)$i('price') ?: null, 'original_price' => (float)$i('original_price') ?: null,
                'inclusions' => $i('inclusions'), 'nights' => (int)$i('nights') ?: null, 'pax' => (int)$i('pax') ?: null,
                'is_published' => $i('is_published') ? 1 : 0, 'is_featured' => $i('is_featured') ? 1 : 0,
                'sort_order' => (int)$i('sort_order'), 'meta_title' => $i('meta_title'), 'meta_description' => $i('meta_description'),
                'updated_at' => date('c'),
            ];
        }
        // offers
        return [
            'title' => $i('title'), 'subtitle' => $i('subtitle'), 'description' => $i('description'),
            'discount_type' => $i('discount_type', 'percent'), 'discount_value' => (float)$i('discount_value'),
            'code' => $i('code'), 'starts_at' => $i('starts_at') ?: null, 'ends_at' => $i('ends_at') ?: null,
            'is_published' => $i('is_published') ? 1 : 0, 'is_featured' => $i('is_featured') ? 1 : 0, 'sort_order' => (int)$i('sort_order'),
        ];
    }
}
