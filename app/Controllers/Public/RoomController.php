<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\RoomType;
use App\Models\Booking;
use App\Models\Content;

class RoomController extends Controller
{
    public function index(): string
    {
        $rooms = RoomType::published();
        $in  = $this->input('check_in');
        $out = $this->input('check_out');
        // Attach availability if dates were searched.
        if ($in && $out) {
            foreach ($rooms as &$r) {
                $r['available'] = Booking::availableUnits((int)$r['id'], $in, $out);
            }
            unset($r);
        }
        return $this->view('public.rooms-index', [
            'active' => 'accommodations',
            'rooms'  => $rooms,
            'check_in' => $in, 'check_out' => $out,
            'guests' => $this->input('guests'),
            'title'  => 'Accommodations — RGE Hotel, Kalanggaman Island, Leyte',
            'metaDescription' => 'Browse rooms and suites at RGE Hotel — from cosy doubles to family rooms and group barkada rooms, steps from the beach near Kalanggaman Island.',
        ]);
    }

    public function show(string $slug): string
    {
        $room = RoomType::findBySlug($slug);
        if (!$room) return $this->abort(404, 'Room not found');

        $photos = RoomType::photos((int)$room['id']);
        $cover = $room['cover'] ?? ($photos[0]['filename'] ?? null);

        return $this->view('public.room-show', [
            'active'    => 'accommodations',
            'room'      => $room,
            'photos'    => $photos,
            'amenities' => RoomType::amenities((int)$room['id']),
            'packages'  => RoomType::packages((int)$room['id']),
            'offers'    => RoomType::offers((int)$room['id']),
            'reviews'   => Content::reviews('room_type', (int)$room['id'], 10),
            'title'     => $room['meta_title'] ?: ($room['name'] . ' — RGE Hotel'),
            'metaDescription' => $room['meta_description'] ?: $room['summary'],
            'ogImage'   => $cover ? url('assets/img/' . $cover . '-full.webp') : null,
            'jsonld'    => $this->jsonLd($room, $cover),
        ]);
    }

    private function jsonLd(array $room, ?string $cover): string
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type'    => 'HotelRoom',
            'name'     => $room['name'],
            'description' => $room['summary'],
            'occupancy' => ['@type' => 'QuantitativeValue', 'maxValue' => $room['max_occupancy']],
            'image'     => $cover ? url('assets/img/' . $cover . '-full.webp') : null,
            'offers'    => [
                '@type' => 'Offer',
                'price' => $room['base_price'],
                'priceCurrency' => 'PHP',
                'availability' => 'https://schema.org/InStock',
            ],
        ];
        return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES) . '</script>';
    }
}
