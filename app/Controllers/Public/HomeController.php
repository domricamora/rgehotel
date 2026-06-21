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
        $phone   = Setting::get('contact_phone', $h['phone'] ?? '');
        $email   = Setting::get('contact_email', $h['email'] ?? '');
        $address = Setting::get('contact_address', '');
        $sameAs  = array_values(array_filter([
            Setting::get('facebook_url', ''),
            Setting::get('instagram_url', ''),
        ]));
        $data = [
            '@context' => 'https://schema.org',
            '@type'    => 'Hotel',
            'name'     => $h['legal_name'],
            'description' => 'A modern beachfront hotel in Palompon, Leyte, gateway to Kalanggaman Island.',
            'url'      => site_url('/'),
            'email'    => $email,
            'image'    => site_url('assets/img/general/hero-island-full.webp'),
            'priceRange' => '₱₱',
            'address'  => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress'   => $address ?: null,
                'addressLocality' => $h['locality'],
                'addressRegion'   => $h['region'],
                'addressCountry'  => $h['country'],
            ]),
            'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $h['lat'], 'longitude' => $h['lng']],
        ];
        if ($phone)  $data['telephone'] = $phone;
        if ($sameAs) $data['sameAs'] = $sameAs;
        if ($rating['count'] > 0) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $rating['avg'],
                'reviewCount' => $rating['count'],
            ];
        }
        return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}
