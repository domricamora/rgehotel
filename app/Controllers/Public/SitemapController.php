<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\RoomType;
use App\Models\Content;
use App\Models\Setting;

/**
 * Dynamic XML sitemap — regenerates from published content on each request.
 */
class SitemapController extends Controller
{
    public function index(): string
    {
        $urls = [];
        $add = function (string $path, string $priority = '0.6', string $freq = 'weekly', ?string $lastmod = null) use (&$urls) {
            $urls[] = ['loc' => site_url($path), 'priority' => $priority, 'freq' => $freq, 'lastmod' => $lastmod];
        };

        // Core pages
        $add('/', '1.0', 'daily');
        $add('/accommodations', '0.9', 'daily');
        $add('/services', '0.8', 'weekly');
        $add('/packages', '0.8', 'weekly');
        $add('/offers', '0.7', 'weekly');
        $add('/reviews', '0.6', 'weekly');
        $add('/about', '0.5', 'monthly');
        $add('/contact', '0.5', 'monthly');
        if (Setting::get('restaurant_published', '0') === '1') {
            $add('/restaurant', '0.6', 'weekly');
        }

        // Detail pages
        foreach (RoomType::published() as $r) {
            $add('/accommodations/' . $r['slug'], '0.8', 'weekly', $r['updated_at'] ?? null);
        }
        foreach (Content::services() as $s) {
            $add('/services/' . $s['slug'], '0.6', 'monthly', $s['updated_at'] ?? null);
        }
        foreach (Content::packages() as $p) {
            $add('/packages/' . $p['slug'], '0.6', 'monthly', $p['updated_at'] ?? null);
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>';
            if (!empty($u['lastmod'])) {
                $xml .= '<lastmod>' . substr((string) $u['lastmod'], 0, 10) . '</lastmod>';
            }
            $xml .= '<changefreq>' . $u['freq'] . '</changefreq>';
            $xml .= '<priority>' . $u['priority'] . '</priority></url>' . "\n";
        }
        $xml .= '</urlset>' . "\n";

        header('Content-Type: application/xml; charset=UTF-8');
        header('Cache-Control: public, max-age=3600');
        echo $xml;
        exit;
    }
}
