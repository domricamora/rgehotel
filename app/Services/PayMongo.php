<?php
namespace App\Services;

/**
 * PayMongo hosted Checkout Sessions (https://docs.paymongo.com).
 * Test mode uses sk_test_... secret keys, live mode sk_live_....
 *
 * Note: PayMongo amounts are integer CENTAVOS, not decimal pesos.
 */
class PayMongo
{
    private const API = 'https://api.paymongo.com/v2/checkout_sessions';

    /** Card payments are rejected below this amount; e-wallets have no floor. */
    private const CARD_MINIMUM = 100.00;

    private array $cfg;

    public function __construct()
    {
        $this->cfg = config('payments.paymongo');
    }

    public function isConfigured(): bool
    {
        $key = (string) ($this->cfg['secret_key'] ?? '');
        return ($this->cfg['enabled'] ?? false) && $key !== '' && !str_contains($key, 'REPLACE');
    }

    public function isLive(): bool
    {
        return ($this->cfg['mode'] ?? 'test') === 'live';
    }

    /**
     * Hosted checkout for an arbitrary amount against a booking — covers both the
     * initial reservation payment and an in-house folio balance settlement.
     * The reference number is echoed back on the webhook, so it must be unique.
     *
     * Returns the response `data` node: ['id' => 'cs_...', 'attributes' => [...]].
     */
    public function createCheckoutSession(
        array $booking,
        float $amount,
        string $referenceNumber,
        string $description,
        string $successUrl,
        string $cancelUrl
    ): array {
        $payload = ['data' => ['attributes' => [
            'line_items' => [[
                'name'     => $description,
                'amount'   => self::toCentavos($amount),
                'currency' => $booking['currency'] ?: 'PHP',
                'quantity' => 1,
            ]],
            'payment_method_types' => $this->methodsFor($amount),
            'reference_number'     => $referenceNumber,
            'description'          => $description,
            'success_url'          => $successUrl,
            'cancel_url'           => $cancelUrl,
            'send_email_receipt'   => true,
            'show_description'     => true,
            'show_line_items'      => true,
            'billing' => array_filter([
                'name'  => $booking['guest_name']  ?? null,
                'email' => $booking['guest_email'] ?? null,
                'phone' => $booking['guest_phone'] ?? null,
            ]),
        ]]];

        $res = $this->request('POST', self::API, $payload);
        return $res['data'] ?? [];
    }

    /** Pesos → integer centavos. PayMongo rejects decimals. */
    public static function toCentavos(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Verify the Paymongo-Signature header against the raw request body.
     * Header format: t=<unix>,te=<test signature>,li=<live signature>.
     *
     * The signed string is documented as "{t}.{raw body}", but PayMongo's own
     * best-practices sample HMACs the raw body alone — so both forms are accepted
     * (same secret either way) and the matching variant is logged. Never throws.
     */
    public function verifySignature(string $rawBody, string $header): bool
    {
        $secret = (string) ($this->cfg['webhook_secret'] ?? '');
        if ($secret === '' || str_contains($secret, 'REPLACE') || $header === '') return false;

        $parts = [];
        foreach (explode(',', $header) as $chunk) {
            $kv = explode('=', trim($chunk), 2);
            if (count($kv) === 2) $parts[$kv[0]] = $kv[1];
        }
        $timestamp = $parts['t'] ?? '';
        $expected  = $this->isLive() ? 'li' : 'te';

        $candidates = [
            'timestamped' => hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret),
            'body-only'   => hash_hmac('sha256', $rawBody, $secret),
        ];

        foreach (['te', 'li'] as $field) {
            $given = (string) ($parts[$field] ?? '');
            if ($given === '') continue;
            foreach ($candidates as $variant => $computed) {
                if (hash_equals($computed, $given)) {
                    if ($field !== $expected) {
                        logger('PayMongo webhook: signature matched ' . $field
                            . ' but mode is ' . ($this->cfg['mode'] ?? 'test'), 'payments');
                    }
                    if ($variant !== 'timestamped') {
                        logger('PayMongo webhook: signature matched the body-only variant', 'payments');
                    }
                    return true;
                }
            }
        }
        return false;
    }

    /** Card has a floor, so drop it for small folio balances rather than fail the call. */
    private function methodsFor(float $amount): array
    {
        $methods = array_values($this->cfg['payment_method_types'] ?? ['gcash']);
        if ($amount < self::CARD_MINIMUM && in_array('card', $methods, true)) {
            $methods = array_values(array_diff($methods, ['card']));
            logger('PayMongo: card dropped for ' . money($amount) . ' (below '
                . money(self::CARD_MINIMUM) . ' minimum)', 'payments');
        }
        if (!$methods) {
            throw new \RuntimeException('PayMongo: no payment method available for ' . money($amount));
        }
        return $methods;
    }

    private function request(string $method, string $url, array $body): array
    {
        $ca = dirname(__DIR__, 2) . '/config/cacert.pem';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_USERPWD        => $this->cfg['secret_key'] . ':',
            CURLOPT_TIMEOUT        => 30,
        ]);
        if (is_file($ca)) curl_setopt($ch, CURLOPT_CAINFO, $ca);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($res === false) { $err = curl_error($ch); curl_close($ch); throw new \RuntimeException('PayMongo cURL: ' . $err); }
        curl_close($ch);
        $data = json_decode($res, true) ?: [];
        if ($code >= 400) {
            throw new \RuntimeException('PayMongo API ' . $code . ': ' . self::errorText($data, $res));
        }
        return $data;
    }

    /** PayMongo returns errors as {"errors":[{"detail":"...","code":"..."}]}. */
    private static function errorText(array $data, string $raw): string
    {
        $details = array_filter(array_map(
            fn($e) => $e['detail'] ?? null,
            $data['errors'] ?? []
        ));
        return $details ? implode('; ', $details) : $raw;
    }
}
