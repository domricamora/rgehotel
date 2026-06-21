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

    /** Invoice for the full room booking total (initial reservation payment). */
    public function createInvoice(array $booking): array
    {
        return $this->createCustomInvoice(
            $booking,
            (float) $booking['total'],
            $booking['reference'],
            'RGE Hotel booking ' . $booking['reference'],
            url('/booking/' . $booking['reference'] . '/confirmation'),
            url('/booking/' . $booking['reference'] . '/pay')
        );
    }

    /**
     * Invoice for an arbitrary amount against a booking — used to settle an
     * in-house folio balance (room service, amenities, other charges) online.
     * The external_id must be unique per Xendit invoice.
     */
    public function createCustomInvoice(
        array $booking,
        float $amount,
        string $externalId,
        string $description,
        string $successUrl,
        string $failureUrl
    ): array {
        $payload = [
            'external_id'           => $externalId,
            'amount'                => round($amount, 2),
            'currency'              => $booking['currency'] ?: 'PHP',
            'payer_email'           => $booking['guest_email'],
            'description'           => $description,
            'success_redirect_url'  => $successUrl,
            'failure_redirect_url'  => $failureUrl,
        ];
        return $this->request('POST', 'https://api.xendit.co/v2/invoices', $payload);
    }

    private function request(string $method, string $url, array $body): array
    {
        $ca = dirname(__DIR__, 2) . '/config/cacert.pem';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_USERPWD        => $this->cfg['secret_key'] . ':',
            CURLOPT_TIMEOUT        => 30,
        ]);
        if (is_file($ca)) curl_setopt($ch, CURLOPT_CAINFO, $ca);
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
