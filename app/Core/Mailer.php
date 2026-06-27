<?php
namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * HTML mailer built on PHPMailer (vendored at lib/PHPMailer).
 *
 * Transport is chosen from config('mail'):
 *   - log_only = true  → append to storage/logs/mail.log (local dev / no MTA)
 *   - smtp.host set    → SMTP (recommended for production; creds in config.local.php)
 *   - otherwise        → PHP mail()
 */
class Mailer
{
    private static bool $loaded = false;

    private static function bootstrap(): void
    {
        if (self::$loaded) return;
        $base = dirname(__DIR__, 2) . '/lib/PHPMailer/src/';
        require_once $base . 'Exception.php';
        require_once $base . 'PHPMailer.php';
        require_once $base . 'SMTP.php';
        self::$loaded = true;
    }

    /**
     * @param string|array $to single address or [email => name] / [email, email...]
     */
    public static function send($to, string $subject, string $htmlBody, ?string $replyTo = null): bool
    {
        $cfg       = config('mail', []);
        $fromName  = $cfg['from_name']  ?? config('app.name', 'RGE Hotel');
        $fromEmail = $cfg['from_email'] ?? 'info@rgehotel.com';
        $html      = self::wrap($subject, $htmlBody);

        // Dev / no-MTA: log instead of sending.
        if (!empty($cfg['log_only'])) {
            $path = $cfg['log_path'] ?? (dirname(__DIR__, 2) . '/storage/logs/mail.log');
            @mkdir(dirname($path), 0777, true);
            $rcpt = is_array($to) ? implode(', ', array_keys($to) === range(0, count($to) - 1) ? $to : array_keys($to)) : $to;
            file_put_contents($path, '[' . date('c') . "] TO: $rcpt | SUBJECT: $subject\n" . $html . "\n" . str_repeat('-', 60) . "\n", FILE_APPEND | LOCK_EX);
            return true;
        }

        self::bootstrap();
        $mail = new PHPMailer(true);
        try {
            $smtp = $cfg['smtp'] ?? [];
            if (!empty($smtp['host'])) {
                $mail->isSMTP();
                $mail->Host       = $smtp['host'];
                $mail->Port       = (int) ($smtp['port'] ?? 587);
                if (!empty($smtp['username'])) {
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtp['username'];
                    $mail->Password = $smtp['password'] ?? '';
                }
                $enc = strtolower((string) ($smtp['encryption'] ?? 'tls'));
                if ($enc === 'ssl')      $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                elseif ($enc === 'tls')  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                else                     $mail->SMTPAutoTLS = false;
                $mail->Timeout = 20;
            } else {
                $mail->isMail();
            }

            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom($fromEmail, $fromName);
            foreach (self::normalizeRecipients($to) as [$addr, $name]) {
                $mail->addAddress($addr, $name);
            }
            if ($replyTo) $mail->addReplyTo($replyTo);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = trim(preg_replace('/\s+/', ' ', strip_tags($htmlBody)));
            $mail->send();
            return true;
        } catch (PHPMailerException | \Throwable $e) {
            logger('Mailer error: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /** @return array<array{0:string,1:string}> list of [email, name] */
    private static function normalizeRecipients($to): array
    {
        if (is_string($to)) return [[$to, '']];
        $out = [];
        foreach ($to as $k => $v) {
            if (is_int($k)) $out[] = [(string) $v, ''];      // [email, email]
            else            $out[] = [(string) $k, (string) $v]; // [email => name]
        }
        return $out;
    }

    /** Wrap body in a full HTML document; <base> lets relative asset URLs resolve in clients that honor it. */
    private static function wrap(string $subject, string $body): string
    {
        $base = rtrim(site_url('/'), '/') . '/';
        return '<!doctype html><html><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<base href="' . e($base) . '">'
            . '<title>' . e($subject) . '</title></head>'
            . '<body style="margin:0;background:#f4f1ea;padding:24px;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#2b2620">'
            . '<div style="max-width:640px;margin:0 auto;background:#fff;padding:32px;border-radius:4px">'
            . $body
            . '</div></body></html>';
    }
}
