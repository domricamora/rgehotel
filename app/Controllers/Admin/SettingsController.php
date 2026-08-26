<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Setting;

class SettingsController extends Controller
{
    public function index(): string
    {
        if (!Auth::isAdmin()) return $this->abort(403, 'Only the super admin can manage API credentials.');
        $settings = Setting::all();
        $paymongoConfig = config('payments.paymongo', []);
        $paypalConfig = config('payments.paypal', []);
        $settings['paymongo_enabled'] = Setting::get('paymongo_enabled', ($paymongoConfig['enabled'] ?? false) ? '1' : '0');
        $settings['paymongo_mode'] = Setting::get('paymongo_mode', $paymongoConfig['mode'] ?? 'test');
        $settings['paypal_enabled'] = Setting::get('paypal_enabled', ($paypalConfig['enabled'] ?? false) ? '1' : '0');
        $settings['paypal_mode'] = Setting::get('paypal_mode', $paypalConfig['mode'] ?? 'sandbox');
        return $this->view('admin.settings', [
            'active'=>'settings','pageTitle'=>'Settings','settings'=>$settings,
            'credentialStatus' => [
                'paymongo' => !empty(Setting::get('paymongo_secret_key', $paymongoConfig['secret_key'] ?? '')) && !str_contains((string) Setting::get('paymongo_secret_key', $paymongoConfig['secret_key'] ?? ''), 'REPLACE'),
                'paymongo_webhook' => !empty(Setting::get('paymongo_webhook_secret', $paymongoConfig['webhook_secret'] ?? '')) && !str_contains((string) Setting::get('paymongo_webhook_secret', $paymongoConfig['webhook_secret'] ?? ''), 'REPLACE'),
                'paypal' => !empty(Setting::get('paypal_client_id', $paypalConfig['client_id'] ?? '')) && !str_contains((string) Setting::get('paypal_client_id', $paypalConfig['client_id'] ?? ''), 'REPLACE'),
                'paypal_webhook' => !empty(Setting::get('paypal_webhook_id', $paypalConfig['webhook_id'] ?? '')) && !str_contains((string) Setting::get('paypal_webhook_id', $paypalConfig['webhook_id'] ?? ''), 'REPLACE'),
            ],
        ], 'admin');
    }

    public function save(): string
    {
        $this->requirePost();
        if (!Auth::isAdmin()) return $this->abort(403, 'Only the super admin can manage API credentials.');
        $keys = ['hero_headline','hero_subhead','hero_image','hero_video','intro_heading','intro_body',
            'contact_email','contact_phone','contact_address','facebook_url','instagram_url'];
        foreach ($keys as $k) {
            Setting::set($k, (string)$this->input($k, ''));
        }
        Setting::set('restaurant_published', $this->input('restaurant_published') ? '1' : '0');
        Setting::set('online_fee_percent', (string) max(0, (float) $this->input('online_fee_percent', '0')));
        $credentials = [
            'paymongo_mode', 'paymongo_secret_key', 'paymongo_webhook_secret',
            'paypal_mode', 'paypal_client_id', 'paypal_client_secret', 'paypal_webhook_id',
        ];
        foreach ($credentials as $key) {
            $value = trim((string) $this->input($key, ''));
            if ($value !== '') Setting::set($key, $value);
        }
        Setting::set('paymongo_enabled', $this->input('paymongo_enabled') ? '1' : '0');
        Setting::set('paypal_enabled', $this->input('paypal_enabled') ? '1' : '0');
        flash('success', 'Settings saved.');
        redirect('/admin/settings');
        return '';
    }
}
