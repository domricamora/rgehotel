<?php
/**
 * Add the "Full House" accommodation — the entire house, with the full set of
 * amenities and the interior photo gallery (assets/img/rooms/full-house/).
 * Idempotent and safe on live data (does NOT truncate like seed.php).
 *
 *   php scripts/add-full-house.php
 *
 * Pricing: base ₱9,000 / weekend ₱10,000.
 * Run scripts/build-fullhouse-images.php first to generate the photos.
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

$slug = 'full-house';
$name = 'Full House';
$fields = [
    'slug'            => $slug,
    'name'            => $name,
    'summary'         => 'Rent the entire house — exclusive use for your whole group or family.',
    'description'     => "Book the Full House and enjoy exclusive use of the entire property — multiple "
        . "bedrooms, a private bathroom set-up and shared living space, all to yourselves. "
        . "Ideal for big families, barkadas and special occasions who want privacy, comfort "
        . "and the whole place to themselves, just steps from Leyte's white-sand shore and the "
        . "gateway to Kalanggaman Island.",
    'max_occupancy'   => 16,
    'adults'          => 16,
    'children'        => 0,
    'beds'            => 'Multiple Bedrooms',
    'size_sqm'        => 150,
    'base_price'      => 9000,
    'weekend_price'   => 10000,
    'total_units'     => 1, // there is one whole house
    'view'            => 'Sea View',
    'sort_order'      => 8, // after the barkada rooms (last)
    'is_published'    => 1,
    'is_featured'     => 0,
    'meta_title'      => "$name — RGE Hotel, Kalanggaman Island, Leyte",
    'meta_description'=> 'Rent the entire house at RGE Hotel — exclusive use, full amenities, steps from the beach near Kalanggaman Island, Leyte.',
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

// Full amenities — every amenity in the catalogue.
$db->delete('room_type_amenities', ['room_type_id' => $id]);
$all = $db->all('SELECT id FROM amenities ORDER BY id');
foreach ($all as $a) {
    $db->insert('room_type_amenities', ['room_type_id' => $id, 'amenity_id' => (int) $a['id']]);
}
$out('Amenities attached (full set): ' . count($all));

// Photo gallery — assets/img/rooms/full-house/1..N (whatever was generated).
$db->delete('room_photos', ['room_type_id' => $id]);
$idx = 0;
for ($n = 1; $n <= 12; $n++) {
    $base = "rooms/full-house/$n";
    if (!is_file(__DIR__ . "/../assets/img/$base-full.webp")) continue;
    $db->insert('room_photos', [
        'room_type_id' => $id,
        'filename'     => $base,
        'alt'          => "$name at RGE Hotel",
        'is_cover'     => $idx === 0 ? 1 : 0,
        'sort_order'   => $idx,
    ]);
    $idx++;
}
$out("Photos attached: $idx");

// One physical unit (the whole house).
$prefix = 'FH';
$have   = (int) $db->scalar('SELECT COUNT(*) FROM rooms WHERE room_type_id = ?', [$id]);
for ($u = $have + 1; $u <= $fields['total_units']; $u++) {
    $db->insert('rooms', [
        'room_type_id' => $id,
        'code'         => sprintf('%s%d-%02d', $prefix, $id, $u),
        'floor'        => '1',
        'status'       => 'available',
        'housekeeping' => 'clean',
    ]);
}
$out('Inventory units total: ' . $db->scalar('SELECT COUNT(*) FROM rooms WHERE room_type_id = ?', [$id]));

$db->commit();
$out("\nDone — Full House is live (₱9,000 base / ₱10,000 weekend, full amenities).");
