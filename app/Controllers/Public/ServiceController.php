<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Content;

class ServiceController extends Controller
{
    public function index(): string
    {
        return $this->view('public.services-index', [
            'active'   => 'services',
            'services' => Content::services(),
            'title'    => 'Tours, Island Hopping & Services — RGE Hotel',
            'metaDescription' => 'Kalanggaman Island hopping, Leyte tours, scuba diving, water sports, airport transfers and car rental — book your adventures with RGE Hotel.',
        ]);
    }

    public function show(string $slug): string
    {
        $service = Content::service($slug);
        if (!$service) return $this->abort(404, 'Service not found');
        return $this->view('public.service-show', [
            'active'  => 'services',
            'service' => $service,
            'related' => array_slice(array_filter(Content::services(), fn($s) => $s['id'] != $service['id']), 0, 3),
            'title'   => $service['meta_title'] ?: ($service['name'] . ' — RGE Hotel'),
            'metaDescription' => $service['meta_description'] ?: $service['summary'],
            'ogImage' => $service['image'] ? url('assets/img/' . $service['image'] . '-full.webp') : null,
        ]);
    }
}
