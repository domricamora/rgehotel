<?php
namespace App\Services;

/**
 * PayPal Orders v2 API. Sandbox vs live selected by config mode.
 */
class PayPal
{
    private array $cfg;
    private string $base;

    public function __construct()
    {
        $this->cfg = config('payments.paypal');
        $this->base = ($this->cfg['mode'] ?? 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function isConfigured(): bool
    {
        return ($this->cfg['enabled'] ?? false) && !empty($this->cfg['client_id'])
            && !str_contains($this->cfg['client_id'], 'REPLACE');
    }

    private function token(): string
    {
        $ch = curl_init($this->base . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $this->cfg['client_id'] . ':' . $this->cfg['client_secret'],
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_TIMEOUT => 30,
        ]);
        $ca = dirname(__DIR__, 2) . '/config/cacert.pem';
        if (is_file($ca)) curl_setopt($ch, CURLOPT_CAINFO, $ca);
        $res = curl_exec($ch);
        if ($res === false) { $e = curl_error($ch); curl_close($ch); throw new \RuntimeException('PayPal auth cURL: ' . $e); }
        curl_close($ch);
        $data = json_decode($res, true) ?: [];
        if (empty($data['access_token'])) throw new \RuntimeException('PayPal auth failed: ' . $res);
        return $data['access_token'];
    }

    public function createOrder(array $booking): array
    {
        $body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $booking['reference'],
                'description'  => 'RGE Hotel booking ' . $booking['reference'],
                'amount' => ['currency_code' => $booking['currency'] ?: 'PHP', 'value' => number_format((float)$booking['total'], 2, '.', '')],
            ]],
            'application_context' => [
                'brand_name' => 'RGE Hotel',
                'user_action' => 'PAY_NOW',
                'return_url' => url('/payment/paypal/return?ref=' . $booking['reference']),
                'cancel_url' => url('/booking/' . $booking['reference'] . '/pay'),
            ],
        ];
        return $this->request('POST', '/v2/checkout/orders', $body);
    }

    public function captureOrder(string $orderId): array
    {
        return $this->request('POST', '/v2/checkout/orders/' . $orderId . '/capture', []);
    }

    public function approvalUrl(array $order): string
    {
        foreach ($order['links'] ?? [] as $link) {
            if (($link['rel'] ?? '') === 'approve') return $link['href'];
        }
        throw new \RuntimeException('PayPal: approval URL not found');
    }

    private function request(string $method, string $path, array $body): array
    {
        $ch = curl_init($this->base . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $body ? json_encode($body) : '{}',
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $this->token()],
            CURLOPT_TIMEOUT => 30,
        ]);
        $ca = dirname(__DIR__, 2) . '/config/cacert.pem';
        if (is_file($ca)) curl_setopt($ch, CURLOPT_CAINFO, $ca);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($res === false) { $e = curl_error($ch); curl_close($ch); throw new \RuntimeException('PayPal cURL: ' . $e); }
        curl_close($ch);
        $data = json_decode($res, true) ?: [];
        if ($code >= 400) throw new \RuntimeException('PayPal API ' . $code . ': ' . ($data['message'] ?? $res));
        return $data;
    }
}
