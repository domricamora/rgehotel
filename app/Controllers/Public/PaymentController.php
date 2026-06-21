<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Booking;
use App\Models\Folio;
use App\Services\Xendit;

class PaymentController extends Controller
{
    /** Handle the payment-method choice from the pay page. */
    public function process(string $ref): string
    {
        $this->requirePost();
        $booking = Booking::findByReference($ref);
        if (!$booking) return $this->abort(404, 'Booking not found');
        if ($booking['payment_status'] === 'paid') { redirect('/booking/' . $ref . '/confirmation'); return ''; }

        $method = $this->input('method');

        try {
            if ($method === 'xendit') {
                $xendit = new Xendit();
                if ($xendit->isConfigured()) {
                    $invoice = $xendit->createInvoice($booking);
                    $this->recordPayment($booking, 'xendit', 'pending', $invoice['id'] ?? null, $invoice['invoice_url'] ?? null, $invoice);
                    redirect($invoice['invoice_url']);
                    return '';
                }
                return $this->simulate($booking, 'xendit');
            }

            // Pay-at-hotel / reserve without online payment.
            if ($method === 'reserve') {
                $this->db->update('bookings', ['status' => 'confirmed', 'updated_at' => date('c')], ['id' => $booking['id']]);
                $this->recordPayment($booking, 'cash', 'pending', null, null, ['note' => 'Pay at hotel']);
                flash('success', 'Your reservation is confirmed. Please settle payment on arrival.');
                redirect('/booking/' . $ref . '/confirmation');
                return '';
            }
        } catch (\Throwable $e) {
            logger('Payment error: ' . $e->getMessage(), 'error');
            flash('error', 'We could not start the payment. Please try again or contact us.');
            redirect('/booking/' . $ref . '/pay');
            return '';
        }

        flash('error', 'Please choose a payment method.');
        redirect('/booking/' . $ref . '/pay');
        return '';
    }

    /** Dev sandbox simulation when live/test keys are not yet configured. */
    private function simulate(array $booking, string $provider): string
    {
        $this->recordPayment($booking, $provider, 'paid', 'SIM-' . $booking['reference'], null,
            ['simulated' => true, 'note' => 'Sandbox simulation — no real charge']);
        Folio::reconcile((int)$booking['id']);
        flash('info', 'Sandbox demo: payment via ' . ucfirst($provider) . ' was simulated (no real charge). Add API keys in config to enable live processing.');
        redirect('/booking/' . $booking['reference'] . '/confirmation');
        return '';
    }

    private function recordPayment(array $booking, string $provider, string $status, ?string $externalId, ?string $ref, $payload): void
    {
        $this->db->insert('payments', [
            'booking_id' => $booking['id'], 'provider' => $provider, 'method' => $provider,
            'amount' => $booking['total'], 'currency' => $booking['currency'], 'status' => $status,
            'external_id' => $externalId, 'external_ref' => $ref,
            'payload' => is_string($payload) ? $payload : json_encode($payload),
        ]);
    }

    /** Xendit server-to-server webhook (invoice paid). */
    public function xenditWebhook(): string
    {
        $raw = file_get_contents('php://input');
        $token = $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? '';
        $expected = config('payments.xendit.webhook_token');
        if (!$expected || !hash_equals((string)$expected, (string)$token)) {
            http_response_code(401);
            return $this->json(['ok' => false, 'error' => 'invalid token']);
        }
        $data = json_decode($raw, true) ?: [];
        $extId = $data['id'] ?? ($data['external_id'] ?? null);
        $status = strtolower($data['status'] ?? '');
        if ($extId && in_array($status, ['paid', 'settled'])) {
            $payment = $this->db->first('SELECT * FROM payments WHERE external_id = ?', [$extId]);
            if ($payment) {
                $this->db->update('payments', ['status' => 'paid', 'updated_at' => date('c')], ['id' => $payment['id']]);
                Folio::reconcile((int)$payment['booking_id']);
                logger('Xendit webhook: booking ' . $payment['booking_id'] . ' payment settled', 'payments');
            }
        }
        return $this->json(['ok' => true]);
    }

    public function genericReturn(string $ref): string
    {
        redirect('/booking/' . $ref . '/confirmation');
        return '';
    }
}
