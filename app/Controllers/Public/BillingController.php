<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Booking;
use App\Models\RoomType;
use App\Models\Folio;
use App\Services\Xendit;

/**
 * Guest-facing folio: shows the room booking, any in-house charges
 * (room service, amenities, other charges) and lets the guest settle
 * the outstanding balance online via Xendit.
 */
class BillingController extends Controller
{
    /** GET /booking/{ref}/billing — itemised folio + pay button. */
    public function show(string $ref): string
    {
        $booking = Booking::findByReference($ref);
        if (!$booking) return $this->abort(404, 'Booking not found');
        $room = RoomType::find((int) $booking['room_type_id']);
        $xendit = config('payments.xendit');
        return $this->view('public.booking-billing', [
            'active'      => '',
            'booking'     => $booking,
            'room'        => $room,
            'charges'     => Folio::charges((int) $booking['id']),
            'summary'     => Folio::summary($booking),
            'xenditReady' => ($xendit['enabled'] ?? false) && !str_contains($xendit['secret_key'] ?? '', 'REPLACE'),
            'title'       => 'Your bill — ' . $ref,
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

        $externalId = $ref . '-FOLIO-' . strtoupper(bin2hex(random_bytes(3)));

        try {
            $xendit = new Xendit();
            if ($xendit->isConfigured()) {
                $invoice = $xendit->createCustomInvoice(
                    $booking,
                    $balance,
                    $externalId,
                    'RGE Hotel folio balance — ' . $ref,
                    url('/booking/' . $ref . '/billing'),
                    url('/booking/' . $ref . '/billing')
                );
                $this->recordPayment($booking, $balance, 'pending', $externalId, $invoice['invoice_url'] ?? null, $invoice);
                redirect($invoice['invoice_url']);
                return '';
            }
            // Sandbox: no live keys configured — simulate a successful charge.
            $this->recordPayment($booking, $balance, 'paid', $externalId, null,
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

    private function recordPayment(array $booking, float $amount, string $status, ?string $externalId, ?string $ref, $payload): void
    {
        $this->db->insert('payments', [
            'booking_id'   => $booking['id'],
            'provider'     => 'xendit',
            'method'       => 'xendit',
            'amount'       => round($amount, 2),
            'currency'     => $booking['currency'] ?: 'PHP',
            'status'       => $status,
            'external_id'  => $externalId,
            'external_ref' => $ref,
            'payload'      => is_string($payload) ? $payload : json_encode($payload),
        ]);
    }
}
