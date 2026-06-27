<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Booking;
use App\Models\Folio;
use App\Services\Xendit;
use App\Services\PayPal;

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
                $base   = (float) $booking['total'];
                $fee    = online_fee_amount($base);
                $charge = round($base + $fee, 2);
                $desc   = 'RGE Hotel booking ' . $booking['reference']
                        . ($fee > 0 ? ' (incl. ' . online_fee_percent() . '% online fee)' : '');
                $xendit = new Xendit();
                if ($xendit->isConfigured()) {
                    $invoice = $xendit->createCustomInvoice(
                        $booking, $charge, $booking['reference'], $desc,
                        site_url('/booking/' . $booking['reference'] . '/confirmation'),
                        site_url('/booking/' . $booking['reference'] . '/pay')
                    );
                    $this->recordPayment($booking, 'xendit', 'pending', $invoice['id'] ?? null, $invoice['invoice_url'] ?? null, $invoice, $charge);
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
        $charge = round((float) $booking['total'] + online_fee_amount((float) $booking['total']), 2);
        $this->recordPayment($booking, $provider, 'paid', 'SIM-' . $booking['reference'], null,
            ['simulated' => true, 'note' => 'Sandbox simulation — no real charge'], $charge);
        Folio::reconcile((int)$booking['id']);
        flash('info', 'Sandbox demo: payment via ' . ucfirst($provider) . ' was simulated (no real charge). Add API keys in config to enable live processing.');
        redirect('/booking/' . $booking['reference'] . '/confirmation');
        return '';
    }

    private function recordPayment(array $booking, string $provider, string $status, ?string $externalId, ?string $ref, $payload, ?float $amount = null): void
    {
        $this->db->insert('payments', [
            'booking_id' => $booking['id'], 'provider' => $provider, 'method' => $provider,
            'amount' => $amount ?? $booking['total'], 'currency' => $booking['currency'], 'status' => $status,
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

    /** PayPal buyer returns after approving — capture the order. */
    public function paypalReturn(): string
    {
        $token = $this->input('token'); // PayPal order id
        $ref = $this->input('ref');
        $booking = $ref ? Booking::findByReference($ref) : null;
        try {
            $paypal = new PayPal();
            if ($paypal->isConfigured() && $token) {
                $capture = $paypal->captureOrder($token);
                if (($capture['status'] ?? '') === 'COMPLETED') {
                    $payment = $this->db->first('SELECT * FROM payments WHERE external_id = ?', [$token]);
                    if ($payment) {
                        $this->db->update('payments', ['status' => 'paid', 'updated_at' => date('c')], ['id' => $payment['id']]);
                        Folio::reconcile((int)$payment['booking_id']);
                        $booking = Booking::find((int)$payment['booking_id']);
                    }
                }
            }
        } catch (\Throwable $e) {
            logger('PayPal capture error: ' . $e->getMessage(), 'error');
        }
        if ($booking) { redirect('/booking/' . $booking['reference'] . '/confirmation'); return ''; }
        return $this->abort(404, 'Booking not found');
    }

    /**
     * PayPal webhook — capture backstop for buyers who approve but never return.
     * Verifies the signature, then settles on CHECKOUT.ORDER.APPROVED (capturing
     * first) or PAYMENT.CAPTURE.COMPLETED. Idempotent.
     */
    public function paypalWebhook(): string
    {
        $raw = file_get_contents('php://input');
        $event = json_decode($raw, true) ?: [];
        $type = $event['event_type'] ?? '';

        try {
            $paypal = new PayPal();
            if (!$paypal->isConfigured() || !$paypal->webhookId()) {
                return $this->json(['ok' => true, 'skipped' => 'paypal webhook not configured']);
            }
            $headers = [
                'auth_algo'         => $_SERVER['HTTP_PAYPAL_AUTH_ALGO'] ?? '',
                'cert_url'          => $_SERVER['HTTP_PAYPAL_CERT_URL'] ?? '',
                'transmission_id'   => $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] ?? '',
                'transmission_sig'  => $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] ?? '',
                'transmission_time' => $_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'] ?? '',
            ];
            if (!$paypal->verifyWebhookSignature($headers, $event)) {
                http_response_code(400);
                logger('PayPal webhook: signature verification FAILED (' . $type . ')', 'payments');
                return $this->json(['ok' => false, 'error' => 'invalid signature']);
            }

            $res = $event['resource'] ?? [];
            $orderId = $res['supplementary_data']['related_ids']['order_id']
                ?? ($type === 'CHECKOUT.ORDER.APPROVED' ? ($res['id'] ?? null) : null);

            if ($orderId) {
                $payment = $this->db->first('SELECT * FROM payments WHERE external_id = ?', [$orderId]);
                if ($payment && $payment['status'] !== 'paid') {
                    if ($type === 'CHECKOUT.ORDER.APPROVED') {
                        $cap = $paypal->captureOrder($orderId);
                        if (($cap['status'] ?? '') !== 'COMPLETED') {
                            return $this->json(['ok' => true, 'note' => 'capture not completed']);
                        }
                    }
                    $this->db->update('payments', ['status' => 'paid', 'updated_at' => date('c')], ['id' => $payment['id']]);
                    Folio::reconcile((int) $payment['booking_id']);
                    logger('PayPal webhook: booking ' . $payment['booking_id'] . ' settled via ' . $type, 'payments');
                }
            }
        } catch (\Throwable $e) {
            logger('PayPal webhook error: ' . $e->getMessage(), 'error');
        }
        return $this->json(['ok' => true]);
    }

    public function genericReturn(string $ref): string
    {
        redirect('/booking/' . $ref . '/confirmation');
        return '';
    }
}
