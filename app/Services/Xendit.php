<?php
namespace App\Services;

/**
 * Xendit Invoices API (https://developers.xendit.co).
 * Sandbox uses development secret keys (xnd_development_...).
 */
class Xendit
{
    private array $cfg;

    public function __construct()
    {
        $this->cfg = config('payments.xendit');
    }

    public function isConfigured(): bool
    {
        return ($this->cfg['enabled'] ?? false) && !empty($this->cfg['secret_key'])
            && !str_contains($this->cfg['secret_key'], 'REPLACE');
    }

    public function createInvoice(array $booking): array
    {
        $payload = [
            'external_id'           => $booking['reference'],
            'amount'                => (float) $booking['total'],
            'currency'              => $booking['currency'] ?: 'PHP',
            'payer_email'           => $booking['guest_email'],
            'description'           => 'RGE Hotel booking ' . $booking['reference'],
            'success_redirect_url'  => url('/booking/' . $booking['reference'] . '/confirmation'),
            'failure_redirect_url'  => url('/booking/' . $booking['reference'] . '/pay'),
        ];
        return $this->request('POST', 'https://api.xendit.co/v2/invoices', $payload);
    }

    private function request(string $method, string $url, array $body): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_USERPWD        => $this->cfg['secret_key'] . ':',
            CURLOPT_TIMEOUT        => 30,
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($res === false) { $err = curl_error($ch); curl_close($ch); throw new \RuntimeException('Xendit cURL: ' . $err); }
        curl_close($ch);
        $data = json_decode($res, true) ?: [];
        if ($code >= 400) {
            throw new \RuntimeException('Xendit API ' . $code . ': ' . ($data['message'] ?? $res));
        }
        return $data;
    }
}
