<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Content;
use App\Models\Setting;

class RestaurantController extends Controller
{
    public function index(): string
    {
        // Restaurant page is kept unpublished on the live server per spec.
        $published = Setting::get('restaurant_published', '0') === '1'
            || (config('features.restaurant_published') === true);
        if (!$published) {
            http_response_code(404);
            return $this->view('errors.404', [
                'message' => 'Our restaurant page is coming soon.',
                'title' => 'Coming Soon — RGE Hotel',
            ]);
        }
        return $this->view('public.restaurant', [
            'active' => '',
            'menu'   => Content::menu(),
            'title'  => 'Restaurant — RGE Hotel',
            'metaDescription' => 'Fresh seafood and Filipino favourites by the beach at RGE Hotel restaurant.',
        ]);
    }
}
