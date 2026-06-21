<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Setting;

class RestaurantController extends Controller
{
    public function index(): string
    {
        $cats = $this->db->all('SELECT * FROM menu_categories ORDER BY sort_order, id');
        foreach ($cats as &$c) {
            $c['items'] = $this->db->all('SELECT * FROM menu_items WHERE category_id=? ORDER BY sort_order, id', [$c['id']]);
        }
        return $this->view('admin.restaurant', [
            'active'=>'restaurant','pageTitle'=>'Restaurant Menu','cats'=>$cats,
            'published' => Setting::get('restaurant_published','0') === '1',
        ], 'admin');
    }

    public function save(string $id): string
    {
        $this->requirePost();
        // id = 'publish' toggles publish flag; otherwise update a menu item.
        if ($id === 'publish') {
            Setting::set('restaurant_published', $this->input('published') ? '1' : '0');
            flash('success', 'Restaurant visibility updated.');
            redirect('/admin/restaurant');
            return '';
        }
        $this->db->update('menu_items', [
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'price' => (float)$this->input('price'),
            'is_available' => $this->input('is_available') ? 1 : 0,
        ], ['id' => $id]);
        flash('success', 'Menu item updated.');
        redirect('/admin/restaurant');
        return '';
    }
}
