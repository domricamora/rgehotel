<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\RoomType;
use App\Models\Content;
use App\Models\Setting;

class HomeController extends Controller
{
    public function index(): string
    {
        $rating = Content::ratingSummary();
        $data = [
            'active'         => '',
            'rooms'          => RoomType::featured(3),
            'services'       => Content::featuredServices(4),
            'packages'       => Content::featuredPackages(3),
            'offers'         => Content::featuredOffers(3),
            'reviews'        => Content::reviews('hotel', null, 3),
            'rating'         => $rating,
            'settings'       => Setting::all(),
            'title'          => 'RGE Hotel — Beachfront Escape near Kalanggaman Island, Leyte',
            'jsonld'         => $this->jsonLd($rating),
        ];
        return $this->view('public.home', $data);
    }

    public function subscribe(): string
    {
        $email = filter_var($this->input('email', ''), FILTER_VALIDATE_EMAIL);
        if ($email) {
            try { $this->db->insert('subscribers', ['email' => $email, 'source' => 'home']); }
            catch (\Throwable $e) { /* duplicate — ignore */ }
            flash('success', 'Thank you! We\'ll keep you posted.');
        } else {
            flash('error', 'Please enter a valid email address.');
        }
        redirect('/#newsletter');
        return '';
    }

    private function jsonLd(array $rating): string
    {
        $h = config('hotel');
        $data = [
            '@context' => 'https://schema.org',
            '@type'    => 'Hotel',
            'name'     => $h['legal_name'],
            'description' => 'A modern beachfront hotel in Palompon, Leyte, gateway to Kalanggaman Island.',
            'email'    => $h['email'],
            'image'    => url('assets/img/general/hero-island-full.webp'),
            'address'  => [
                '@type' => 'PostalAddress',
                'addressLocality' => $h['locality'],
                'addressRegion'   => $h['region'],
                'addressCountry'  => $h['country'],
            ],
            'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $h['lat'], 'longitude' => $h['lng']],
        ];
        if ($rating['count'] > 0) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $rating['avg'],
                'reviewCount' => $rating['count'],
            ];
        }
        return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES) . '</script>';
    }
}
