<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Booking;
use App\Models\RoomType;
use App\Models\Folio;
use App\Services\PayMongo;

/**
 * Guest-facing folio: shows the room booking, any in-house charges
 * (room service, amenities, other charges) and lets the guest settle
 * the outstanding balance online via PayMongo.
 */
class BillingController extends Controller
{
    /** GET /booking/{ref}/billing — itemised folio + pay button. */
    public function show(string $ref): string
    {
        $booking = Booking::findByReference($ref);
        if (!$booking) return $this->abort(404, 'Booking not found');
        $room = RoomType::find((int) $booking['room_type_id']);
        return $this->view('public.booking-billing', [
            'active'       => '',
            'booking'      => $booking,
            'room'         => $room,
            'charges'      => Folio::charges((int) $booking['id']),
            'summary'      => Folio::summary($booking),
            'gatewayReady' => (new PayMongo())->isConfigured(),
            'title'        => 'Your bill — ' . $ref,
            'noindex'      => true,
        ]);
    }

    /** POST /booking/{ref}/billing/pay — start an online payment for the balance. */
    public function pay(string $ref): string
    {
        $this->requirePost();
        $booking = Booking::findByReference($ref);
        if (!$booking) return $this->abort(404, 'Booking not found');

        $summary = Folio::summary($booking);
        $balance = $summary['balance'];
        if ($balance <= 0) {
            flash('info', 'Your bill is already settled in full.');
            redirect('/booking/' . $ref . '/billing');
            return '';
        }

        $reference = $ref . '-FOLIO-' . strtoupper(bin2hex(random_bytes(3)));
        $fee    = online_fee_amount($balance);
        $charge = round($balance + $fee, 2);
        $desc   = 'RGE Hotel folio balance — ' . $ref . ($fee > 0 ? ' (incl. ' . online_fee_percent() . '% online fee)' : '');

        try {
            $gateway = new PayMongo();
            if ($gateway->isConfigured()) {
                $session = $gateway->createCheckoutSession(
                    $booking,
                    $charge,
                    $reference,
                    $desc,
                    site_url('/booking/' . $ref . '/billing'),
                    site_url('/booking/' . $ref . '/billing')
                );
                $this->recordPayment($booking, $charge, 'pending', $session['id'] ?? null, $reference, $session);
                redirect($session['attributes']['checkout_url']);
                return '';
            }
            // Sandbox: no live keys configured — simulate a successful charge.
            $this->recordPayment($booking, $charge, 'paid', 'SIM-' . $reference, $reference,
                ['simulated' => true, 'note' => 'Sandbox simulation — no real charge']);
            Folio::reconcile((int) $booking['id']);
            flash('info', 'Sandbox demo: your folio balance of ' . money($balance) . ' was settled (no real charge).');
            redirect('/booking/' . $ref . '/billing');
            return '';
        } catch (\Throwable $e) {
            logger('Folio payment error: ' . $e->getMessage(), 'error');
            flash('error', 'We could not start the payment. Please try again or contact the front desk.');
            redirect('/booking/' . $ref . '/billing');
            return '';
        }
    }

    /** external_id = PayMongo session id; external_ref = the reference we sent (webhook fallback). */
    private function recordPayment(array $booking, float $amount, string $status, ?string $externalId, ?string $externalRef, $payload): void
    {
        $this->db->insert('payments', [
            'booking_id'   => $booking['id'],
            'provider'     => 'paymongo',
            'method'       => 'paymongo',
            'amount'       => round($amount, 2),
            'currency'     => $booking['currency'] ?: 'PHP',
            'status'       => $status,
            'external_id'  => $externalId,
            'external_ref' => $externalRef,
            'payload'      => is_string($payload) ? $payload : json_encode($payload),
        ]);
    }
}
