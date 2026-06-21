<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Setting;

class SettingsController extends Controller
{
    public function index(): string
    {
        return $this->view('admin.settings', [
            'active'=>'settings','pageTitle'=>'Settings','settings'=>Setting::all(),
        ], 'admin');
    }

    public function save(): string
    {
        $this->requirePost();
        $keys = ['hero_headline','hero_subhead','intro_heading','intro_body',
            'contact_email','contact_phone','contact_address','facebook_url','instagram_url'];
        foreach ($keys as $k) {
            Setting::set($k, (string)$this->input($k, ''));
        }
        Setting::set('restaurant_published', $this->input('restaurant_published') ? '1' : '0');
        flash('success', 'Settings saved.');
        redirect('/admin/settings');
        return '';
    }
}
