<?php
/**
 * Seed the database with roles, permissions, staff, accommodations, services,
 * packages, offers, reviews, restaurant menu, pages, and settings.
 *
 * Pricing/content are reasonable placeholders for a Leyte beachfront hotel and
 * are fully editable in the admin backend. Run: php scripts/seed.php
 */
require __DIR__ . '/../app/Core/helpers.php';
spl_autoload_register(function ($class) {
    if (!str_starts_with($class, 'App\\')) return;
    $f = __DIR__ . '/../app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($f)) require $f;
});

use App\Core\Database;

$db = Database::instance();
$pdo = $db->pdo();
$manifest = json_decode(@file_get_contents(__DIR__ . '/../storage/media-manifest.json'), true) ?: ['rooms'=>[],'services'=>[],'amenities'=>[],'general'=>[]];

// Wipe (child-first) for a clean reseed.
$wipe = ['role_permissions','permissions','room_type_amenities','room_photos','package_room_types',
    'package_services','offer_room_types','booking_rooms','payments','bookings','rooms','room_rates',
    'reviews','menu_items','menu_categories','offers','packages','services','amenities','room_types',
    'pages','settings','contact_messages','subscribers','users','roles'];
$pdo->exec('PRAGMA foreign_keys = OFF');
foreach ($wipe as $t) { $pdo->exec("DELETE FROM $t"); $pdo->exec("DELETE FROM sqlite_sequence WHERE name='$t'"); }
$pdo->exec('PRAGMA foreign_keys = ON');

$db->beginTransaction();

/* ---------------------------------------------------------------- ROLES */
$roles = [
    'super_admin' => ['Super Admin', 'Full control over the entire system.'],
    'manager'     => ['Manager', 'Oversight, approvals and content management.'],
    'front_desk'  => ['Front Desk / Reservations', 'Manage bookings, guests and check-in/out.'],
    'housekeeping'=> ['Housekeeping', 'Room status and cleaning schedules.'],
    'restaurant'  => ['Restaurant / F&B', 'Manage the restaurant menu and items.'],
    'accounting'  => ['Accounting', 'Payments, invoices, refunds and reports.'],
];
$roleId = [];
foreach ($roles as $slug => [$name, $desc]) {
    $roleId[$slug] = $db->insert('roles', ['slug'=>$slug,'name'=>$name,'description'=>$desc]);
}

/* ---------------------------------------------------------- PERMISSIONS */
$perms = [
    'dashboard.view'      => ['View dashboard', 'general'],
    'bookings.view'       => ['View bookings', 'bookings'],
    'bookings.manage'     => ['Manage bookings', 'bookings'],
    'guests.view'         => ['View guests', 'bookings'],
    'rooms.view'          => ['View rooms', 'rooms'],
    'rooms.manage'        => ['Manage room types & rates', 'rooms'],
    'housekeeping.view'   => ['View housekeeping', 'housekeeping'],
    'housekeeping.manage' => ['Manage housekeeping', 'housekeeping'],
    'services.manage'     => ['Manage services & tours', 'content'],
    'packages.manage'     => ['Manage packages', 'content'],
    'offers.manage'       => ['Manage offers & promos', 'content'],
    'reviews.moderate'    => ['Moderate reviews', 'content'],
    'restaurant.manage'   => ['Manage restaurant menu', 'restaurant'],
    'payments.view'       => ['View payments', 'finance'],
    'payments.manage'     => ['Manage payments & refunds', 'finance'],
    'reports.view'        => ['View reports', 'finance'],
    'content.manage'      => ['Manage pages & content', 'content'],
    'users.manage'        => ['Manage users', 'admin'],
    'roles.manage'        => ['Manage roles', 'admin'],
    'settings.manage'     => ['Manage settings', 'admin'],
];
$permId = [];
foreach ($perms as $slug => [$name, $grp]) {
    $permId[$slug] = $db->insert('permissions', ['slug'=>$slug,'name'=>$name,'grp'=>$grp]);
}
$grant = function (string $role, array $slugs) use ($db, $roleId, $permId) {
    foreach ($slugs as $s) {
        $db->insert('role_permissions', ['role_id'=>$roleId[$role], 'permission_id'=>$permId[$s]]);
    }
};
$grant('super_admin', array_keys($perms));
$grant('manager', ['dashboard.view','bookings.view','bookings.manage','guests.view','rooms.view','rooms.manage',
    'housekeeping.view','services.manage','packages.manage','offers.manage','reviews.moderate','restaurant.manage',
    'payments.view','reports.view','content.manage']);
$grant('front_desk', ['dashboard.view','bookings.view','bookings.manage','guests.view','rooms.view','housekeeping.view','payments.view']);
$grant('housekeeping', ['dashboard.view','housekeeping.view','housekeeping.manage','rooms.view']);
$grant('restaurant', ['dashboard.view','restaurant.manage']);
$grant('accounting', ['dashboard.view','bookings.view','payments.view','payments.manage','reports.view']);

/* ----------------------------------------------------------------- USERS */
$pw = password_hash('password', PASSWORD_DEFAULT);
$users = [
    ['super_admin', 'Nick Ricamora', 'admin@rgehotel.com', 'General Manager', 'Management'],
    ['manager',     'Maria Santos',  'manager@rgehotel.com', 'Operations Manager', 'Management'],
    ['front_desk',  'Jenny Cruz',    'frontdesk@rgehotel.com', 'Front Desk Officer', 'Front Office'],
    ['housekeeping','Rosa Dela Cruz','housekeeping@rgehotel.com', 'Housekeeping Supervisor', 'Housekeeping'],
    ['restaurant',  'Chef Ramon',    'restaurant@rgehotel.com', 'F&B Supervisor', 'Food & Beverage'],
    ['accounting',  'Liza Reyes',    'accounting@rgehotel.com', 'Accountant', 'Finance'],
];
foreach ($users as [$role, $name, $email, $pos, $dept]) {
    $db->insert('users', ['role_id'=>$roleId[$role],'name'=>$name,'email'=>$email,'password_hash'=>$pw,
        'position'=>$pos,'department'=>$dept,'is_active'=>1]);
}

/* ------------------------------------------------------------- AMENITIES */
$amenities = [
    ['wifi','Free Wi-Fi','wifi'], ['air-con','Air Conditioning','wind'], ['private-bath','Private Bathroom','bath'],
    ['hot-shower','Hot & Cold Shower','shower-head'], ['tv','Flat-screen TV','tv'], ['fridge','Mini Refrigerator','refrigerator'],
    ['balcony','Private Balcony','door-open'],
    ['parking','Free Parking','car'], ['beach-access','Beach Access','umbrella'], ['room-service','Room Service','concierge-bell'],
    ['housekeeping','Daily Housekeeping','sparkles'], ['linens','Fresh Towels & Linens','bed'], ['desk','Work Desk','lamp-desk'],
];
$amenityId = [];
foreach ($amenities as $a) {
    $amenityId[$a[0]] = $db->insert('amenities', ['slug'=>$a[0],'name'=>$a[1],'icon'=>$a[2],'category'=>'general']);
}

/* ----------------------------------------------------------- ROOM TYPES */
// slug => [name, summary, desc, max_occ, adults, children, beds, size, price, weekend, units, view, featured, amenities[]]
$baseAmen = ['wifi','air-con','private-bath','hot-shower','tv','linens','housekeeping','coffee'];
$roomTypes = [
    ['twin-room','Twin Room',
        'Cozy room with two single beds — ideal for friends or colleagues.',
        "Our Twin Room offers two comfortable single beds in a bright, air-conditioned space designed for friends, colleagues or solo travellers who like room to spread out. Wake up refreshed and step out minutes from the white-sand shoreline that leads to Kalanggaman Island.",
        2,2,0,'2 Single Beds',18,1800,2100,4,'Garden View',0, array_merge($baseAmen,['fridge'])],
    ['double-room','Double Room',
        'Warm, modern room with a queen bed for couples.',
        "A snug retreat for two, the Double Room pairs a plush queen bed with cool air-conditioning and a private hot-and-cold shower. The perfect base after a day of island hopping or exploring Leyte's heritage towns.",
        2,2,0,'1 Queen Bed',20,2000,2300,4,'Garden View',1, array_merge($baseAmen,['fridge'])],
    ['double-or-twin','Double or Twin Room',
        'Flexible bedding — choose a queen bed or two singles.',
        "Travelling as a couple or sharing with a friend? The Double or Twin Room flexes to suit, configured as one queen bed or two singles on request. Comfortable, contemporary and steps from the beach.",
        2,2,0,'1 Queen or 2 Singles',20,2000,2300,3,'Garden View',0, array_merge($baseAmen,['fridge'])],
    ['triple-room','Triple Room',
        'Spacious room sleeping three — great for small families.',
        "With three single beds (or a queen plus a single), the Triple Room gives small families and groups of friends room to relax together. Bright, airy and fitted with all the comforts for an easy island stay.",
        3,3,0,'3 Single Beds',26,2600,2900,3,'Garden View',0, array_merge($baseAmen,['fridge','balcony'])],
    ['family-room','Family Room',
        'Generous family space for up to four guests.',
        "Our Family Room comfortably hosts up to four guests with a thoughtful layout, extra storage and a private bathroom. Bring the kids, unpack, and let Leyte's beaches become your playground.",
        4,2,2,'1 Queen + 2 Singles',32,3200,3600,3,'Sea View',1, array_merge($baseAmen,['fridge','balcony','sea-view'])],
    ['super-family-room','Super Family Room',
        'Our largest family space — sleeps up to five with room to spare.',
        "The Super Family Room is our most generous family stay, comfortably hosting up to five guests with a roomy layout, extra beds and a private bathroom. Perfect for bigger families and groups who want to stay together, with Leyte's white-sand beaches just steps from the door.",
        5,4,1,'2 Queen + 1 Single',40,2000,2200,3,'Sea View',0, array_merge($baseAmen,['fridge','balcony','sea-view'])],
    ['suite','Suite',
        'Our most refined room — extra space, sea breeze and a private balcony.',
        "The Suite is RGE Hotel's signature stay: a generous, light-filled room with premium bedding, a private balcony catching the sea breeze, and elevated finishes throughout. The ideal choice for honeymooners and travellers who want a little more.",
        2,2,1,'1 King Bed',40,4500,5200,2,'Sea View',1, array_merge($baseAmen,['fridge','balcony','sea-view','room-service','breakfast'])],
    ['barkada-room-a','Barkada Room A',
        'Big group room for your barkada — sleeps up to eight.',
        "Built for the barkada, this large group room sleeps up to eight across multiple beds, so the whole crew can stay together. Perfect for friends heading to Kalanggaman Island for a weekend of sun, sand and good company.",
        8,8,0,'Multiple Beds',45,4000,4600,2,'Garden View',0, array_merge($baseAmen,['fridge','beach-access'])],
    ['barkada-room-b','Barkada Room B',
        'Group room for friends — sleeps up to six.',
        "A comfortable group room for up to six friends, Barkada Room B keeps everyone under one roof at a great value. Spacious, breezy and an easy walk to the shore.",
        6,6,0,'Multiple Beds',38,3500,4000,2,'Garden View',0, array_merge($baseAmen,['fridge','beach-access'])],
    ['full-house','Full House',
        'Rent the entire house — exclusive use for your whole group or family.',
        "Book the Full House and enjoy exclusive use of the entire property — multiple bedrooms, a private bathroom set-up and shared living space, all to yourselves. Ideal for big families, barkadas and special occasions who want privacy, comfort and the whole place to themselves, just steps from Leyte's white-sand shore and the gateway to Kalanggaman Island.",
        16,16,0,'Multiple Bedrooms',150,9000,10000,1,'Sea View',0, array_merge($baseAmen,['fridge','balcony','sea-view','breakfast','parking','beach-access','room-service','desk'])],
];
$roomTypeId = [];
$sort = 0;
foreach ($roomTypes as $r) {
    [$slug,$name,$summary,$desc,$occ,$ad,$ch,$beds,$size,$price,$weekend,$units,$view,$featured,$amen] = $r;
    $id = $db->insert('room_types', [
        'slug'=>$slug,'name'=>$name,'summary'=>$summary,'description'=>$desc,'max_occupancy'=>$occ,
        'adults'=>$ad,'children'=>$ch,'beds'=>$beds,'size_sqm'=>$size,'base_price'=>$price,'weekend_price'=>$weekend,
        'total_units'=>$units,'view'=>$view,'sort_order'=>$sort++,'is_published'=>1,'is_featured'=>$featured,
        'meta_title'=>"$name — RGE Hotel, Kalanggaman Island, Leyte",
        'meta_description'=>$summary,
    ]);
    $roomTypeId[$slug] = $id;
    foreach ($amen as $a) {
        if (isset($amenityId[$a])) $db->insert('room_type_amenities', ['room_type_id'=>$id,'amenity_id'=>$amenityId[$a]]);
    }
    // Photos from manifest.
    $photos = $manifest['rooms'][$slug] ?? [];
    foreach ($photos as $idx => $base) {
        $db->insert('room_photos', ['room_type_id'=>$id,'filename'=>$base,'alt'=>"$name at RGE Hotel",
            'is_cover'=>$idx===0?1:0,'sort_order'=>$idx]);
    }
    // Physical inventory units.
    $prefix = strtoupper(substr(preg_replace('/[^a-z]/','',$slug),0,3));
    for ($u=1; $u<=$units; $u++) {
        $db->insert('rooms', ['room_type_id'=>$id,'code'=>sprintf('%s%d-%02d',$prefix,$id,$u),'floor'=>(string)(($u%2)+1),
            'status'=>'available','housekeeping'=>'clean']);
    }
}

/* -------------------------------------------------------------- SERVICES */
// slug => [name, category, summary, desc, price, unit, duration, highlights, featured]
$services = [
    ['kalanggaman-island-hopping','Kalanggaman Island Hopping','island_hopping',
        'Day trip to the famous Kalanggaman sandbar.',
        "Sail to Kalanggaman Island, Leyte's crown jewel — a slender white sandbar flanked by crystal-clear turquoise water. Includes boat transfers, environmental fees coordination and time to swim, snorkel and soak up paradise.",
        1500,'per person','Full day (approx. 8 hours)',"Boat transfers\nSandbar & swimming\nSnorkelling spots\nPacked lunch option",1],
    ['leyte-island-tour','Leyte Heritage & Island Tour','tour',
        'Discover Leyte\'s history, churches and landscapes.',
        "A guided journey through Leyte's storied towns — from the MacArthur Landing Memorial to centuries-old churches, local markets and scenic coastal views. A rich, comfortable day out with an experienced driver-guide.",
        2500,'per person','Full day',"Air-conditioned vehicle\nLocal guide\nHeritage sites\nHotel pick-up & drop-off",1],
    ['scuba-diving','Scuba Diving','diving',
        'Explore vibrant reefs with certified guides.',
        "Discover the underwater world around Leyte with guided dives suited to beginners and certified divers alike. Includes equipment, a certified divemaster and unforgettable reef encounters.",
        3500,'per dive','Half day',"Equipment included\nCertified divemaster\nReef & wall dives\nBeginner discover-scuba option",0],
    ['water-sports','Water Sports','watersport',
        'Kayaking, paddleboarding, snorkelling and more.',
        "Make a splash right off the shore. Choose from kayaking, stand-up paddleboarding, snorkelling and banana-boat rides — all equipment provided with safety briefing.",
        800,'per activity','1–2 hours',"Kayaks & paddleboards\nSnorkelling gear\nLife vests\nSafety briefing",0],
    ['airport-transfer','Airport Transfer','transfer',
        'Comfortable transfers to and from the airport.',
        "Start and end your trip stress-free with a private transfer between the airport (Tacloban / Ormoc) and RGE Hotel in an air-conditioned vehicle.",
        1200,'per way','As scheduled',"Air-conditioned vehicle\nMeet & greet\nFlexible timing",0],
    ['car-rental','Car Rental with Driver','car',
        'Explore Leyte at your own pace.',
        "Hire a well-maintained vehicle with a professional driver for the day and explore Leyte on your own schedule. Fuel and driver's fee included for local trips.",
        3500,'per day','Per day',"Professional driver\nAir-conditioned vehicle\nFlexible itinerary",0],
];
$serviceId = [];
$sort = 0;
foreach ($services as $s) {
    [$slug,$name,$cat,$summary,$desc,$price,$unit,$dur,$hl,$feat] = $s;
    $serviceId[$slug] = $db->insert('services', [
        'slug'=>$slug,'name'=>$name,'category'=>$cat,'summary'=>$summary,'description'=>$desc,'price'=>$price,
        'price_unit'=>$unit,'duration'=>$dur,'highlights'=>$hl,'image'=>$manifest['services'][$slug] ?? null,
        'is_published'=>1,'is_featured'=>$feat,'sort_order'=>$sort++,
        'meta_title'=>"$name — RGE Hotel",'meta_description'=>$summary,
    ]);
}

/* -------------------------------------------------------------- PACKAGES */
$packages = [
    ['kalanggaman-getaway','Kalanggaman Island Getaway',
        '2 nights + island hopping for two.',
        "Our most popular escape: two nights in a sea-view room, daily breakfast and a full-day Kalanggaman Island hopping trip for two. Everything you need for the perfect Leyte beach weekend.",
        11900,13800,2,2,['suite','family-room'],['kalanggaman-island-hopping'],1],
    ['leyte-explorer','Leyte Explorer',
        '3 nights with a heritage island tour.',
        "Three relaxed nights with daily breakfast plus a guided Leyte Heritage & Island Tour. Ideal for travellers who want to combine beach time with culture and history.",
        16500,19000,3,2,['double-room','triple-room'],['leyte-island-tour','airport-transfer'],1],
    ['honeymoon-escape','Honeymoon Beachfront Escape',
        '2 romantic nights in the Suite.',
        "Celebrate love with two nights in our signature Suite, daily breakfast, a sunset water-sports session and a private island-hopping trip for two. Romance, sea breeze and unforgettable views.",
        15900,18500,2,2,['suite'],['kalanggaman-island-hopping','water-sports'],1],
];
$sort = 0;
foreach ($packages as $p) {
    [$slug,$name,$summary,$desc,$price,$orig,$nights,$pax,$rts,$svcs,$feat] = $p;
    // inclusions list
    $inc = "$nights nights accommodation\nDaily breakfast for $pax";
    foreach ($svcs as $sv) { $inc .= "\n" . ($services[array_search($sv, array_column($services,0))][1] ?? $sv); }
    $cover = null;
    if ($rts && isset($manifest['rooms'][$rts[0]][0])) $cover = $manifest['rooms'][$rts[0]][0];
    $pid = $db->insert('packages', [
        'slug'=>$slug,'name'=>$name,'summary'=>$summary,'description'=>$desc,'price'=>$price,'original_price'=>$orig,
        'price_unit'=>'per package','inclusions'=>$inc,'image'=>$cover,'nights'=>$nights,'pax'=>$pax,
        'is_published'=>1,'is_featured'=>$feat,'sort_order'=>$sort++,
        'meta_title'=>"$name — RGE Hotel",'meta_description'=>$summary,
    ]);
    foreach ($rts as $rt) if (isset($roomTypeId[$rt])) $db->insert('package_room_types', ['package_id'=>$pid,'room_type_id'=>$roomTypeId[$rt]]);
    foreach ($svcs as $sv) if (isset($serviceId[$sv])) $db->insert('package_services', ['package_id'=>$pid,'service_id'=>$serviceId[$sv]]);
}

/* ---------------------------------------------------------------- OFFERS */
$offers = [
    ['early-bird','Early Bird 15% Off','Book 30 days ahead and save',
        "Plan ahead and enjoy 15% off your room rate when you book at least 30 days before arrival.",
        'percent',15,'EARLYBIRD',1,['suite','family-room','double-room']],
    ['stay-3-pay-2','Stay 3, Pay 2','Your third night is on us',
        "Stay three nights and only pay for two. The longer you linger by the sea, the more you save.",
        'percent',33,'STAY3PAY2',1,[]],
    ['summer-splash','Summer Splash','Free water-sports session',
        "Book any sea-view room this season and enjoy a complimentary water-sports session for two.",
        'fixed',1600,'SUMMER',0,['suite','family-room']],
];
$sort = 0;
foreach ($offers as $o) {
    [$slug,$title,$subtitle,$desc,$type,$val,$code,$feat,$rts] = $o;
    $oid = $db->insert('offers', [
        'slug'=>$slug,'title'=>$title,'subtitle'=>$subtitle,'description'=>$desc,'discount_type'=>$type,
        'discount_value'=>$val,'code'=>$code,'image'=>$manifest['offers'][$slug] ?? ($manifest['general']['beach'] ?? null),
        'starts_at'=>date('Y-m-d'),'ends_at'=>date('Y-m-d', strtotime('+6 months')),
        'is_published'=>1,'is_featured'=>$feat,'sort_order'=>$sort++,
    ]);
    foreach ($rts as $rt) if (isset($roomTypeId[$rt])) $db->insert('offer_room_types', ['offer_id'=>$oid,'room_type_id'=>$roomTypeId[$rt]]);
}

/* --------------------------------------------------------------- REVIEWS */
$reviews = [
    ['hotel',null,'Anna Müller','Germany',5,'Paradise found','The staff were incredibly warm and Kalanggaman Island was the most beautiful beach we have ever seen. Spotless rooms and great breakfast.','2026-03-15'],
    ['hotel',null,'James Wilson','Australia',5,'Perfect island base','Great value, friendly team and the island hopping tour was a highlight of our Philippines trip. Highly recommend.','2026-02-20'],
    ['hotel',null,'Mika Tanaka','Japan',4,'Lovely stay','Clean, comfortable and close to the sea. The suite balcony was wonderful at sunset.','2026-04-02'],
    ['hotel',null,'Sofia Reyes','Philippines',5,'Best barkada trip','We booked the Barkada Room and had the best weekend. So much space and the beach is steps away.','2026-01-28'],
    ['room_type','suite','Liam O\'Brien','Ireland',5,'Worth every peso','The Suite is gorgeous — spacious, breezy and the sea view is unreal. Will be back.','2026-03-30'],
    ['room_type','family-room','Grace Tan','Singapore',5,'Great for families','Plenty of room for our family of four and the kids loved the beach access. Staff went above and beyond.','2026-02-11'],
    ['room_type','double-room','Carlos Mendoza','Spain',4,'Cosy and clean','Comfortable bed, strong aircon and hot shower. Excellent value for the location.','2026-04-18'],
    ['hotel',null,'Emma Brown','United Kingdom',5,'Hidden gem','RGE Hotel made our Leyte trip special. Authentic, welcoming and right by the water.','2026-05-05'],
];
foreach ($reviews as $rv) {
    [$type,$subjectSlug,$author,$country,$rating,$title,$body,$date] = $rv;
    $subjectId = ($type==='room_type' && isset($roomTypeId[$subjectSlug])) ? $roomTypeId[$subjectSlug] : null;
    $db->insert('reviews', ['subject_type'=>$type,'subject_id'=>$subjectId,'author_name'=>$author,
        'author_country'=>$country,'rating'=>$rating,'title'=>$title,'body'=>$body,'stay_date'=>$date,'is_approved'=>1]);
}

/* ------------------------------------------------------------ RESTAURANT */
$menu = [
    ['breakfast','Breakfast','Start your island morning right.',[
        ['Filipino Breakfast (Tapsilog)','Cured beef, garlic rice and egg',220],
        ['Longganisa Plate','Sweet local sausage, garlic rice, egg',200],
        ['Continental Breakfast','Bread, fruits, jam and coffee',180],
        ['Pancakes & Fresh Fruit','Stack of pancakes with seasonal fruit',190],
    ]],
    ['filipino','Filipino Favorites','Home-style classics.',[
        ['Chicken Adobo','Braised chicken in soy, vinegar and garlic',280],
        ['Pork Sinigang','Sour tamarind soup with pork and vegetables',320],
        ['Kare-Kare','Oxtail in peanut sauce with bagoong',420],
        ['Pancit Canton','Stir-fried noodles with vegetables',240],
    ]],
    ['seafood','Fresh Seafood','Caught local, served fresh.',[
        ['Grilled Bangus','Grilled milkfish stuffed with tomato and onion',300],
        ['Garlic Butter Shrimp','Fresh shrimp in garlic butter',420],
        ['Kinilaw','Local ceviche in vinegar and calamansi',260],
        ['Seafood Sinigang','Sour soup with mixed seafood',380],
    ]],
    ['grill','From the Grill','Smoky island barbecue.',[
        ['Pork BBQ Skewers','Marinated grilled pork skewers (3 pcs)',180],
        ['Grilled Chicken','Half chicken, island marinade',320],
        ['Grilled Squid','Whole squid, calamansi-soy',360],
    ]],
    ['beverages','Beverages','Cool down island-style.',[
        ['Fresh Buko Juice','Young coconut, served chilled',90],
        ['Mango Shake','Sweet Philippine mango',120],
        ['Calamansi Juice','Refreshing local citrus',80],
        ['Local Beer','Ice-cold bottle',90],
    ]],
    ['desserts','Desserts','A sweet finish.',[
        ['Halo-Halo','Shaved ice with sweets, leche flan and ube',150],
        ['Leche Flan','Classic caramel custard',110],
        ['Mango Float','Layered mango and cream',130],
    ]],
];
$sort = 0;
foreach ($menu as $cat) {
    [$slug,$name,$desc,$items] = $cat;
    $cid = $db->insert('menu_categories', ['slug'=>$slug,'name'=>$name,'description'=>$desc,'sort_order'=>$sort++,'is_published'=>1]);
    $isort = 0;
    foreach ($items as [$iname,$idesc,$iprice]) {
        $db->insert('menu_items', ['category_id'=>$cid,'name'=>$iname,'description'=>$idesc,'price'=>$iprice,
            'is_available'=>1,'is_featured'=>$isort===0?1:0,'sort_order'=>$isort++]);
    }
}

/* ------------------------------------------------------------------ PAGES */
$pages = [
    ['about','About RGE Hotel',
        "RGE Hotel is a warm, family-run beachfront hotel in Palompon, Leyte — the gateway to the world-famous Kalanggaman Island. We combine clean, modern comfort with genuine Filipino hospitality, just steps from the white-sand shore.\n\nWhether you're chasing the turquoise water of the Kalanggaman sandbar, touring Leyte's rich heritage, or simply unwinding by the sea, our team is here to make your stay effortless and memorable.",
        'About RGE Hotel — Kalanggaman Island, Leyte', 'A family-run beachfront hotel in Palompon, Leyte, the gateway to Kalanggaman Island.'],
    ['contact','Contact Us',
        "We'd love to help plan your island escape. Reach us by email at info@rgehotel.com or send a message through the form and our team will get back to you promptly.",
        'Contact RGE Hotel', 'Get in touch with RGE Hotel in Palompon, Leyte.'],
    ['terms','Terms & Conditions',
        "These terms govern bookings made with RGE Hotel. Reservations require valid guest details and are confirmed upon successful payment. Cancellation and refund policies apply as described at the time of booking.",
        'Terms & Conditions — RGE Hotel', 'Booking terms and conditions for RGE Hotel.'],
    ['privacy','Privacy Policy',
        "RGE Hotel respects your privacy. We collect only the information needed to process your booking and improve your stay, and we never sell your data.",
        'Privacy Policy — RGE Hotel', 'How RGE Hotel handles your personal data.'],
];
foreach ($pages as [$slug,$title,$body,$mt,$md]) {
    $db->insert('pages', ['slug'=>$slug,'title'=>$title,'body'=>$body,'meta_title'=>$mt,'meta_description'=>$md,'is_published'=>1]);
}

/* --------------------------------------------------------------- SETTINGS */
$settings = [
    ['hero_headline','Where the island begins','string','home'],
    ['hero_subhead','A modern beachfront escape at the gateway to Kalanggaman Island, Leyte.','string','home'],
    ['hero_image', $manifest['general']['hero-island'] ?? 'general/hero-island','string','home'],
    ['intro_heading','Your island, beautifully simple','string','home'],
    ['intro_body','Clean, modern rooms, warm Filipino hospitality and the clearest water in the Visayas — all just steps from the shore.','string','home'],
    ['contact_email','info@rgehotel.com','string','contact'],
    ['contact_phone','','string','contact'],
    ['contact_address','Palompon, Leyte, Philippines','string','contact'],
    ['facebook_url','','string','social'],
    ['instagram_url','','string','social'],
    ['restaurant_published','0','bool','features'],
];
foreach ($settings as [$k,$v,$type,$grp]) {
    $db->insert('settings', ['key'=>$k,'value'=>$v,'type'=>$type,'grp'=>$grp]);
}

$db->commit();

/* ------------------------------------------------------------------ REPORT */
$count = fn($t) => $db->scalar("SELECT COUNT(*) FROM $t");
echo "Seed complete:\n";
foreach (['roles','permissions','users','amenities','room_types','room_photos','rooms','services','packages','offers','reviews','menu_categories','menu_items','pages','settings'] as $t) {
    printf("  %-18s %d\n", $t, $count($t));
}
echo "\nLogin (dev): admin@rgehotel.com / password  (change before launch)\n";
