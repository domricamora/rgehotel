<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Content;

class ReviewController extends Controller
{
    public function index(): string
    {
        $reviews = Content::reviews(null, null, 60);
        $rating  = Content::ratingSummary();
        return $this->view('public.reviews-index', [
            'active'  => 'reviews',
            'reviews' => $reviews,
            'rating'  => $rating,
            'title'   => 'Guest Reviews — RGE Hotel',
            'metaDescription' => 'Read what guests say about their stay at RGE Hotel near Kalanggaman Island, Leyte.',
            'jsonld'  => $this->jsonLd($rating, $reviews),
        ]);
    }

    private function jsonLd(array $rating, array $reviews): string
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type'    => 'Hotel',
            'name'     => 'RGE Hotel',
            'url'      => site_url('/'),
            'image'    => site_url('assets/img/general/hero-island-full.webp'),
        ];
        if (($rating['count'] ?? 0) > 0) {
            $data['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => $rating['avg'],
                'reviewCount' => $rating['count'],
                'bestRating'  => 5,
                'worstRating' => 1,
            ];
        }
        $items = [];
        foreach (array_slice($reviews, 0, 10) as $rv) {
            $items[] = array_filter([
                '@type'         => 'Review',
                'name'          => $rv['title'] ?: null,
                'reviewBody'    => $rv['body'] ?? null,
                'datePublished' => !empty($rv['created_at']) ? substr((string) $rv['created_at'], 0, 10) : null,
                'author'        => ['@type' => 'Person', 'name' => $rv['author_name'] ?? 'Guest'],
                'reviewRating'  => [
                    '@type'       => 'Rating',
                    'ratingValue' => $rv['rating'],
                    'bestRating'  => 5,
                    'worstRating' => 1,
                ],
            ]);
        }
        if ($items) $data['review'] = $items;
        return jsonld($data);
    }

    public function store(): string
    {
        $this->requirePost();
        $name   = trim((string)$this->input('author_name'));
        $country= trim((string)$this->input('author_country'));
        $rating = (int)$this->input('rating');
        $title  = trim((string)$this->input('title'));
        $body   = trim((string)$this->input('body'));

        if ($name === '' || $rating < 1 || $rating > 5 || $body === '') {
            flash('error', 'Please provide your name, a rating and a short review.');
            redirect('/reviews');
            return '';
        }
        $this->db->insert('reviews', [
            'subject_type' => 'hotel', 'subject_id' => null,
            'author_name' => $name, 'author_country' => $country ?: null,
            'rating' => $rating, 'title' => $title ?: null, 'body' => $body,
            'is_approved' => 0,
        ]);
        flash('success', 'Thank you for your review! It will appear once approved by our team.');
        redirect('/reviews');
        return '';
    }
}
