<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Content;

class PageController extends Controller
{
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
