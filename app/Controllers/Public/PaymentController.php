<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Booking;
use App\Models\Folio;
use App\Services\PayMongo;
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
            if ($method === 'paymongo') {
                $base   = (float) $booking['total'];
                $fee    = online_fee_amount($base);
                $charge = round($base + $fee, 2);
                $desc   = 'RGE Hotel booking ' . $booking['reference']
                        . ($fee > 0 ? ' (incl. ' . online_fee_percent() . '% online fee)' : '');
                $gateway = new PayMongo();
                if ($gateway->isConfigured()) {
                    $session = $gateway->createCheckoutSession(
                        $booking, $charge, $booking['reference'], $desc,
                        site_url('/booking/' . $booking['reference'] . '/confirmation'),
                        site_url('/booking/' . $booking['reference'] . '/pay')
                    );
                    $checkoutUrl = PayMongo::checkoutUrl($session);
                    if ($checkoutUrl === null) {
                        throw new \RuntimeException('PayMongo returned no valid checkout URL');
                    }
                    $this->recordPayment($booking, 'paymongo', 'pending', $session['id'] ?? null,
                        $booking['reference'], $session, $charge);
                    redirect($checkoutUrl);
                    return '';
                }
                return $this->simulate($booking, 'paymongo');
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

    /**
     * Column convention for gateway payments:
     *   external_id  — the gateway's own id (PayMongo checkout session, 'cs_...')
     *   external_ref — the reference_number we sent, so the webhook can resolve the
     *                  row even when the event doesn't carry the session id
     */
    private function recordPayment(array $booking, string $provider, string $status, ?string $externalId, ?string $externalRef, $payload, ?float $amount = null): void
    {
        $this->db->insert('payments', [
            'booking_id' => $booking['id'], 'provider' => $provider, 'method' => $provider,
            'amount' => $amount ?? $booking['total'], 'currency' => $booking['currency'], 'status' => $status,
            'external_id' => $externalId, 'external_ref' => $externalRef,
            'payload' => is_string($payload) ? $payload : json_encode($payload),
        ]);
    }

    /**
     * PayMongo server-to-server webhook. Settles on checkout_session.payment.paid
     * and marks failures on payment.failed. Idempotent — a redelivered event is a
     * no-op once the row is already paid.
     */
    public function paymongoWebhook(): string
    {
        $raw = file_get_contents('php://input');

        $gateway = new PayMongo();
        if (!$gateway->isConfigured()) {
            return $this->json(['ok' => true, 'skipped' => 'paymongo not configured']);
        }
        if (!$gateway->verifySignature($raw, $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '')) {
            logger('PayMongo webhook: signature verification FAILED', 'payments');
            // Non-2xx matters: PayMongo retries on it and flags it in the dashboard, which
            // is how a wrong webhook secret gets noticed instead of silently dropping events.
            // (json() sets the status itself, so passing it here is the only way it sticks.)
            return $this->json(['ok' => false, 'error' => 'invalid signature'], 401);
        }

        try {
            $event = json_decode($raw, true) ?: [];
            $type  = $event['data']['attributes']['type'] ?? '';
            $res   = $event['data']['attributes']['data'] ?? [];
            $payment = $this->resolvePayment($res);

            if ($type === 'checkout_session.payment.paid') {
                if (!$payment) {
                    logger('PayMongo webhook: no payment row for ' . ($res['id'] ?? '?'), 'payments');
                    return $this->json(['ok' => true, 'note' => 'unknown session']);
                }
                if ($payment['status'] === 'paid') {
                    return $this->json(['ok' => true, 'note' => 'already settled']);
                }
                // The checkout session carries the settled payment(s); take the channel
                // and the real amount from there so an underpayment can't mark it paid.
                $paid = $res['attributes']['payments'][0]['attributes'] ?? [];
                $paidAmount = isset($paid['amount']) ? (int) $paid['amount'] : 0;
                $expectedAmount = PayMongo::toCentavos((float) $payment['amount']);
                if ($paidAmount !== $expectedAmount) {
                    logger('PayMongo webhook: amount mismatch for payment ' . $payment['id'], 'payments');
                    return $this->json(['ok' => false, 'error' => 'amount mismatch'], 400);
                }
                $this->db->update('payments', [
                    'status'     => 'paid',
                    'method'     => $paid['source']['type'] ?? 'paymongo',
                    'amount'     => round($paidAmount / 100, 2),
                    'payload'    => $raw,
                    'updated_at' => date('c'),
                ], ['id' => $payment['id']]);
                Folio::reconcile((int) $payment['booking_id']);
                logger('PayMongo webhook: booking ' . $payment['booking_id'] . ' settled via '
                    . ($paid['source']['type'] ?? 'paymongo'), 'payments');
                return $this->json(['ok' => true]);
            }

            if ($type === 'payment.failed') {
                // A payment resource may not carry the session id; when it can't be
                // resolved the stale sweep on the admin payments list is the backstop.
                if ($payment && $payment['status'] === 'pending') {
                    $this->db->update('payments', [
                        'status' => 'failed', 'payload' => $raw, 'updated_at' => date('c'),
                    ], ['id' => $payment['id']]);
                    logger('PayMongo webhook: booking ' . $payment['booking_id'] . ' payment failed', 'payments');
                } else {
                    logger('PayMongo webhook: unresolved payment.failed (' . ($res['id'] ?? '?') . ')', 'payments');
                }
                return $this->json(['ok' => true]);
            }
        } catch (\Throwable $e) {
            logger('PayMongo webhook error: ' . $e->getMessage(), 'error');
        }
        return $this->json(['ok' => true]);
    }

    /** Find the payments row for a webhook resource — by gateway id, else by our reference. */
    private function resolvePayment(array $resource): ?array
    {
        if (!empty($resource['id'])) {
            $row = $this->db->first('SELECT * FROM payments WHERE external_id = ?', [$resource['id']]);
            if ($row) return $row;
        }
        $ref = $resource['attributes']['reference_number'] ?? null;
        return $ref ? $this->db->first('SELECT * FROM payments WHERE external_ref = ?', [$ref]) : null;
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
                logger('PayPal webhook: signature verification FAILED (' . $type . ')', 'payments');
                return $this->json(['ok' => false, 'error' => 'invalid signature'], 400);
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
