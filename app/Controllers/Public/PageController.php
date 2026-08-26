<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Content;

class PageController extends Controller
{
    public function resort(): string
    {
        return $this->view('public.the-resort', [
            'active' => 'resort',
            'title' => 'The Resort - RGE Hotel, Palompon, Leyte',
            'metaDescription' => 'See inside RGE Hotel: bright rooms, welcoming shared spaces and the details that make your island stay comfortable in Palompon, Leyte.',
            'ogImage' => url('assets/img/amenities/7-full.webp'),
        ]);
    }

    public function about(): string
    {
        $page = Content::page('about');
        return $this->view('public.page', [
            'active' => 'about',
            'page'   => $page,
            'heroImage' => 'general/about',
            'title'  => $page['meta_title'] ?? 'About RGE Hotel',
            'metaDescription' => $page['meta_description'] ?? null,
        ]);
    }
}
