<?php
/**
 * Add the "Super Family Room" room type — reusing the Family Room's photos and
 * amenities. Idempotent and safe on live data (does NOT truncate anything like
 * seed.php does). Run locally then upload + hit over HTTP on the server.
 *
 *   php scripts/add-super-family-room.php
 *
 * Pricing: base ₱2,000 / weekend ₱2,200, good for 5 guests.
 */
require __DIR__ . '/../app/Core/helpers.php';
spl_autoload_register(function ($class) {
    if (!str_starts_with($class, 'App\\')) return;
    $f = __DIR__ . '/../app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($f)) require $f;
});

use App\Core\Database;

$db  = Database::instance();
$cli = PHP_SAPI === 'cli';
$out = fn(string $m) => print($cli ? "$m\n" : nl2br(htmlspecialchars($m)) . "\n");

// Source room we clone photos/amenities from.
$family = $db->first('SELECT * FROM room_types WHERE slug = ?', ['family-room']);
if (!$family) { $out('ERROR: family-room not found — nothing to clone from.'); exit(1); }

$slug = 'super-family-room';
$name = 'Super Family Room';
$fields = [
    'slug'            => $slug,
    'name'            => $name,
    'summary'         => 'Our largest family space — sleeps up to five with room to spare.',
    'description'     => "The Super Family Room is our most generous family stay, comfortably "
        . "hosting up to five guests with a roomy layout, extra beds and a private "
        . "bathroom. Perfect for bigger families and groups who want to stay together, "
        . "with Leyte's white-sand beaches just steps from the door.",
    'max_occupancy'   => 5,
    'adults'          => 4,
    'children'        => 1,
    'beds'            => '2 Queen + 1 Single',
    'size_sqm'        => 40,
    'base_price'      => 2000,
    'weekend_price'   => 2200,
    'total_units'     => (int) ($family['total_units'] ?: 2),
    'view'            => $family['view'],
    'sort_order'      => (int) $family['sort_order'], // sits right after Family Room
    'is_published'    => 1,
    'is_featured'     => 0,
    'meta_title'      => "$name — RGE Hotel, Kalanggaman Island, Leyte",
    'meta_description'=> 'Our largest family room at RGE Hotel — sleeps up to five, steps from the beach near Kalanggaman Island, Leyte.',
];

$db->beginTransaction();

$existing = $db->first('SELECT * FROM room_types WHERE slug = ?', [$slug]);
if ($existing) {
    $id = (int) $existing['id'];
    $db->update('room_types', $fields, ['slug' => $slug]);
    $out("Updated existing room type #$id ($slug).");
} else {
    $id = $db->insert('room_types', $fields);
    $out("Created room type #$id ($slug).");
}

// Clone amenities from Family Room.
$db->delete('room_type_amenities', ['room_type_id' => $id]);
$amen = $db->all('SELECT amenity_id FROM room_type_amenities WHERE room_type_id = ?', [(int) $family['id']]);
foreach ($amen as $a) {
    $db->insert('room_type_amenities', ['room_type_id' => $id, 'amenity_id' => (int) $a['amenity_id']]);
}
$out('Amenities cloned: ' . count($amen));

// Reuse Family Room photos (same image files under /assets/img/).
$db->delete('room_photos', ['room_type_id' => $id]);
$photos = $db->all('SELECT filename, sort_order FROM room_photos WHERE room_type_id = ? ORDER BY is_cover DESC, sort_order', [(int) $family['id']]);
foreach ($photos as $idx => $p) {
    $db->insert('room_photos', [
        'room_type_id' => $id,
        'filename'     => $p['filename'],
        'alt'          => "$name at RGE Hotel",
        'is_cover'     => $idx === 0 ? 1 : 0,
        'sort_order'   => $idx,
    ]);
}
$out('Photos reused: ' . count($photos));

// Physical inventory units (only create what's missing — never drop occupied rooms).
$prefix = strtoupper(substr(preg_replace('/[^a-z]/', '', $slug), 0, 3)); // "SUP"
$have   = (int) $db->scalar('SELECT COUNT(*) FROM rooms WHERE room_type_id = ?', [$id]);
for ($u = $have + 1; $u <= $fields['total_units']; $u++) {
    $db->insert('rooms', [
        'room_type_id' => $id,
        'code'         => sprintf('%s%d-%02d', $prefix, $id, $u),
        'floor'        => (string) (($u % 2) + 1),
        'status'       => 'available',
        'housekeeping' => 'clean',
    ]);
}
$out('Inventory units total: ' . $db->scalar('SELECT COUNT(*) FROM rooms WHERE room_type_id = ?', [$id]));

$db->commit();
$out("\nDone — Super Family Room is live (₱2,000 base / ₱2,200 weekend, sleeps 5).");
