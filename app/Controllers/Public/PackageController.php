<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Content;

class PackageController extends Controller
{
    public function index(): string
    {
        return $this->view('public.packages-index', [
            'active'   => 'packages',
            'packages' => Content::packages(),
            'title'    => 'Holiday Packages & Deals — RGE Hotel',
            'metaDescription' => 'Curated stay packages combining rooms, breakfast and Leyte adventures — island getaways, explorer trips and honeymoon escapes at RGE Hotel.',
        ]);
    }

    public function show(string $slug): string
    {
        $package = Content::package($slug);
        if (!$package) return $this->abort(404, 'Package not found');
        return $this->view('public.package-show', [
            'active'    => 'packages',
            'package'   => $package,
            'roomTypes' => Content::packageRoomTypes((int)$package['id']),
            'services'  => Content::packageServices((int)$package['id']),
            'title'     => $package['meta_title'] ?: ($package['name'] . ' — RGE Hotel'),
            'metaDescription' => $package['meta_description'] ?: $package['summary'],
            'ogImage'   => $package['image'] ? url('assets/img/' . $package['image'] . '-full.webp') : null,
        ]);
    }
}
