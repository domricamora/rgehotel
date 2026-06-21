<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Content;

class ReviewController extends Controller
{
    public function index(): string
    {
        return $this->view('public.reviews-index', [
            'active'  => 'reviews',
            'reviews' => Content::reviews(null, null, 60),
            'rating'  => Content::ratingSummary(),
            'title'   => 'Guest Reviews — RGE Hotel',
            'metaDescription' => 'Read what guests say about their stay at RGE Hotel near Kalanggaman Island, Leyte.',
        ]);
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
