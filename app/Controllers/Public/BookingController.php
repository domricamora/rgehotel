<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\RoomType;
use App\Models\Booking;
use App\Models\Content;

class BookingController extends Controller
{
    /** Step 1: booking form (guest details + quote) for a room type. */
    public function create(string $slug): string
    {
        $room = RoomType::findBySlug($slug);
        if (!$room) return $this->abort(404, 'Room not found');

        $in  = $this->input('check_in') ?: date('Y-m-d', strtotime('+7 days'));
        $out = $this->input('check_out') ?: date('Y-m-d', strtotime('+9 days'));
        $rooms = max(1, (int)$this->input('rooms', 1));
        if (strtotime($out) <= strtotime($in)) $out = date('Y-m-d', strtotime($in . ' +1 day'));

        $available = Booking::availableUnits((int)$room['id'], $in, $out);
        $offerCode = trim((string)$this->input('offer_code'));
        $offer = $offerCode ? Content::offerByCode($offerCode) : null;
        $quote = Booking::quote($room, $in, $out, min($rooms, max(1,$available)), $offer);

        return $this->view('public.booking-create', [
            'active' => 'accommodations',
            'room'   => $room,
            'check_in' => $in, 'check_out' => $out, 'rooms' => $rooms,
            'available' => $available, 'quote' => $quote,
            'offer' => $offer, 'offerCode' => $offerCode,
            'adults' => (int)$this->input('adults', $room['adults']),
            'children' => (int)$this->input('children', 0),
            'title' => 'Book ' . $room['name'] . ' — RGE Hotel',
            'noindex' => true,
        ]);
    }

    /** Step 2: persist a pending booking, then go to payment. */
    public function store(string $slug): string
    {
        $this->requirePost();
        $room = RoomType::findBySlug($slug);
        if (!$room) return $this->abort(404, 'Room not found');

        $in  = $this->input('check_in');
        $out = $this->input('check_out');
        $rooms = max(1, (int)$this->input('rooms', 1));
        $name  = trim((string)$this->input('guest_name'));
        $email = filter_var($this->input('guest_email'), FILTER_VALIDATE_EMAIL);

        $errors = [];
        if (!$in || !$out || strtotime($out) <= strtotime($in)) $errors[] = 'Please choose valid check-in and check-out dates.';
        if ($name === '') $errors[] = 'Guest name is required.';
        if (!$email) $errors[] = 'A valid email is required.';

        $available = ($in && $out) ? Booking::availableUnits((int)$room['id'], $in, $out) : 0;
        if ($rooms > $available) $errors[] = 'Sorry, only ' . $available . ' room(s) available for those dates.';

        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('/booking/' . $slug . '?check_in=' . urlencode((string)$in) . '&check_out=' . urlencode((string)$out) . '&rooms=' . $rooms);
            return '';
        }

        $offerCode = trim((string)$this->input('offer_code'));
        $offer = $offerCode ? Content::offerByCode($offerCode) : null;
        $quote = Booking::quote($room, $in, $out, $rooms, $offer);

        $booking = Booking::create([
            'room_type_id' => $room['id'],
            'check_in' => $in, 'check_out' => $out, 'nights' => $quote['nights'], 'rooms_count' => $rooms,
            'adults' => (int)$this->input('adults', 1), 'children' => (int)$this->input('children', 0),
            'guest_name' => $name, 'guest_email' => $email,
            'guest_phone' => trim((string)$this->input('guest_phone')) ?: null,
            'guest_country' => trim((string)$this->input('guest_country')) ?: null,
            'special_requests' => trim((string)$this->input('special_requests')) ?: null,
            'offer_code' => $offer['code'] ?? null,
            'subtotal' => $quote['subtotal'], 'discount' => $quote['discount'], 'total' => $quote['total'],
            'currency' => 'PHP', 'status' => 'pending', 'payment_status' => 'unpaid',
        ]);
        redirect('/booking/' . $booking['reference'] . '/pay');
        return '';
    }

    /** Step 3: choose a payment method. */
    public function pay(string $ref): string
    {
        $booking = Booking::findByReference($ref);
        if (!$booking) return $this->abort(404, 'Booking not found');
        if ($booking['payment_status'] === 'paid') {
            redirect('/booking/' . $ref . '/confirmation');
            return '';
        }
        $room = RoomType::find((int)$booking['room_type_id']);
        return $this->view('public.booking-pay', [
            'active' => '', 'booking' => $booking, 'room' => $room,
            'gatewayReady' => (new \App\Services\PayMongo())->isConfigured(),
            'sandbox' => true,
            'title' => 'Payment — ' . $ref,
            'noindex' => true,
        ]);
    }

    public function confirmation(string $ref): string
    {
        $booking = Booking::findByReference($ref);
        if (!$booking) return $this->abort(404, 'Booking not found');
        $room = RoomType::find((int)$booking['room_type_id']);
        return $this->view('public.booking-confirmation', [
            'active' => '', 'booking' => $booking, 'room' => $room,
            'title' => 'Booking Confirmed — ' . $ref,
            'noindex' => true,
        ]);
    }
}
