<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Content;

class OfferController extends Controller
{
    public function index(): string
    {
        return $this->view('public.offers-index', [
            'active' => 'offers',
            'offers' => Content::offers(),
            'title'  => 'Special Offers & Promos — RGE Hotel',
            'metaDescription' => 'Save on your island stay with RGE Hotel offers — early-bird discounts, stay-longer deals and seasonal promos.',
        ]);
    }
}
